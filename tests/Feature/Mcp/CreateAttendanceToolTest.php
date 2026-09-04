<?php

use App\Mcp\Servers\AttendanceServer;
use App\Mcp\Tools\createAttendance;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Services\TutorMessageService;
use Illuminate\Contracts\JsonSchema\JsonSchema as JsonSchemaContract;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Mcp\Server\Testing\TestResponse;
use Tests\Fixtures\CreateAttendanceTestServer;

/*
|--------------------------------------------------------------------------
| App\Mcp\Tools\createAttendance
|--------------------------------------------------------------------------
|
| Two halves, tested differently:
|
|  - schema(): the bug fixed in 256354e was `$schema->enum(...)`, a method that
|    does not exist on the JsonSchema factory. It only blows up when the schema
|    is actually *built*, so every schema test below goes through
|    `Tool::toArray()` / a real JsonSchema factory instead of asserting on the
|    source. Reverting to `$schema->enum()` makes these fail with a fatal Error.
|
|  - handle(): driven through the real MCP call path (`CreateAttendanceTestServer::tool()`),
|    which is what an MCP client hits, so validation, side effects and the
|    response are all exercised the way production would exercise them.
|
| Some tests at the bottom pin CURRENT BROKEN BEHAVIOUR. They are marked
| "KNOWN DEFECT" and say what the expected behaviour should be. They exist so
| the defects cannot be forgotten, and so they turn red the moment someone
| fixes them (at which point they should be rewritten, not deleted).
|
| Mutation: run with
|   vendor/bin/pest tests/Feature/Mcp/CreateAttendanceToolTest.php \
|     --mutate --class="App\Mcp\Tools\createAttendance"
| One survivor is not killable as the code stands: dropping
| ['relationship_type' => 'parent'] from attach(), because the
| contact_kid.relationship_type column defaults to 'parent', so the mutant is
| behaviourally equivalent.
|
*/

/**
 * A payload that passes validation and that the DB schema can actually store.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function attendancePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'kid' => [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'birth_date' => '2019-05-04',
            'gender' => 'female',
        ],
        'contact' => [
            'first_name' => 'Rosa',
            'last_name' => 'Lopez',
            'phone' => '5512345678',
            'email' => 'rosa@example.com',
            'international_code' => '52',
        ],
        'observations' => 'Llego contenta',
    ], $overrides);
}

/**
 * Calls the tool through the MCP server. Anything it throws propagates.
 *
 * @param  array<string, mixed>  $arguments
 */
function runCreateAttendance(array $arguments): TestResponse
{
    return CreateAttendanceTestServer::tool(createAttendance::class, $arguments);
}

/**
 * The JSON-RPC `result` object an MCP client actually receives.
 *
 * TestResponse only exposes assertions, so the raw envelope is read off its
 * protected JsonRpcResponse. Worth the reflection: it is the only way to assert
 * on the wire format (content block + structuredContent) instead of trusting
 * the tool's own return value.
 *
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>
 */
function createAttendanceResult(array $arguments): array
{
    $testResponse = runCreateAttendance($arguments);

    return (new ReflectionProperty($testResponse, 'response'))
        ->getValue($testResponse)
        ->toArray()['result'];
}

/** Silences the WhatsApp side of the tool and lets us assert which message was sent. */
function mockTutorMessages(): Mockery\MockInterface
{
    return test()->mock(TutorMessageService::class);
}

// ---------------------------------------------------------------------------
// schema()
// ---------------------------------------------------------------------------

describe('schema', function () {
    it('builds a complete input schema without blowing up', function () {
        // Tool::toArray() is what an MCP client's tools/list hits: it runs
        // schema() through the real JsonSchema factory. An undefined method
        // anywhere in schema() is a fatal Error right here.
        $definition = (new createAttendance)->toArray();

        expect($definition)->toHaveKeys(['name', 'description', 'inputSchema'])
            ->and($definition['inputSchema']['type'])->toBe('object')
            ->and($definition['inputSchema']['properties'])
            ->toHaveKeys(['kid', 'contact', 'observations']);
    });

    it('declares gender as a string with an enum, not as a bare enum node', function () {
        // REGRESSION GUARD for 256354e: `$schema->enum([...])` does not exist on
        // Illuminate\JsonSchema\JsonSchemaTypeFactory — enum() lives on the Type
        // returned by string(). Reverting throws Error: Call to undefined method.
        $gender = (new createAttendance)->toArray()['inputSchema']['properties']['kid']['properties']['gender'];

        expect($gender['type'])->toBe('string')
            ->and($gender['enum'])->toBe(['male', 'female'])
            ->and($gender['description'])->toBe('Género del niño');
    });

    it('marks first name, last name, birth date and gender as required on the kid', function () {
        $kid = (new createAttendance)->toArray()['inputSchema']['properties']['kid'];

        expect($kid['type'])->toBe('object')
            ->and($kid['required'])->toBe(['first_name', 'last_name', 'birth_date', 'gender'])
            ->and($kid['properties'])
            ->toHaveKeys(['first_name', 'last_name', 'birth_date', 'gender']);
    });

    it('marks first name, last name and phone as required on the contact', function () {
        $contact = (new createAttendance)->toArray()['inputSchema']['properties']['contact'];

        expect($contact['type'])->toBe('object')
            ->and($contact['required'])->toBe(['first_name', 'last_name', 'phone'])
            ->and($contact['properties'])
            ->toHaveKeys(['first_name', 'last_name', 'phone', 'email', 'international_code']);
    });

    it('declares the date and email string formats', function () {
        $properties = (new createAttendance)->toArray()['inputSchema']['properties'];

        expect($properties['kid']['properties']['birth_date']['format'])->toBe('date')
            ->and($properties['kid']['properties']['birth_date']['type'])->toBe('string')
            ->and($properties['contact']['properties']['email']['format'])->toBe('email')
            ->and($properties['contact']['properties']['email']['type'])->toBe('string');
    });

    it('describes every property so the LLM knows what to send', function () {
        $properties = (new createAttendance)->toArray()['inputSchema']['properties'];

        $descriptions = [
            ...array_column($properties['kid']['properties'], 'description'),
            ...array_column($properties['contact']['properties'], 'description'),
            $properties['observations']['description'],
        ];

        expect($descriptions)->toHaveCount(10)
            ->each->toBeString();

        expect($properties['observations'])
            ->toBe([
                'description' => 'Observaciones adicionales de la asistencia',
                'type' => 'string',
            ]);
    });

    it('accepts the JsonSchema contract, not the concrete factory', function () {
        // Second half of 256354e: narrowing the parameter to the concrete
        // Illuminate\JsonSchema\JsonSchema violates Tool::schema()'s signature.
        $parameter = (new ReflectionMethod(createAttendance::class, 'schema'))->getParameters()[0];

        expect((string) $parameter->getType())->toBe(JsonSchemaContract::class);
    });

    it('returns Type objects keyed by property name when called directly', function () {
        $schema = (new createAttendance)->schema(new JsonSchemaTypeFactory);

        expect($schema)->toHaveKeys(['kid', 'contact', 'observations'])
            ->and($schema['kid'])->toBeInstanceOf(Illuminate\JsonSchema\Types\ObjectType::class)
            ->and($schema['contact'])->toBeInstanceOf(Illuminate\JsonSchema\Types\ObjectType::class)
            ->and($schema['observations'])->toBeInstanceOf(Illuminate\JsonSchema\Types\StringType::class);
    });

    it('exposes a description to the MCP client', function () {
        expect((new createAttendance)->toArray()['description'])
            ->toContain('attendance record')
            ->toContain('notifies their contact');
    });
});

// ---------------------------------------------------------------------------
// handle() — validation
// ---------------------------------------------------------------------------

describe('handle validation', function () {
    it('rejects a missing kid first name with the Spanish message', function () {
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'kid' => ['first_name' => null],
        ]))->assertHasErrors(['El nombre del niño es obligatorio.']);

        expect(Kid::count())->toBe(0)
            ->and(Attendance::count())->toBe(0);
    });

    it('rejects a missing kid last name with the Spanish message', function () {
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'kid' => ['last_name' => null],
        ]))->assertHasErrors(['El apellido del niño es obligatorio.']);
    });

    it('rejects a missing contact phone with the Spanish message', function () {
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'contact' => ['phone' => null],
        ]))->assertHasErrors(['El número de teléfono del tutor es obligatorio.']);

        expect(Contact::count())->toBe(0);
    });

    it('rejects a malformed email with the Spanish message', function () {
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'contact' => ['email' => 'no-es-un-correo'],
        ]))->assertHasErrors(['El correo electrónico no tiene un formato válido.']);
    });

    it('rejects a gender outside the declared enum', function () {
        // KNOWN DEFECT #6: the app runs on locale 'es' (config/app.php) but ships
        // no lang/es/validation.php, so every rule without a custom message in
        // this tool leaks the raw translation key to the MCP client. Pinned as
        // the current contract; fixing it means adding the Spanish lang file.
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'kid' => ['gender' => 'alien'],
        ]))->assertHasErrors(['validation.in']);

        expect(Kid::count())->toBe(0);
    });

    it('rejects an entirely empty payload', function () {
        CreateAttendanceTestServer::tool(createAttendance::class, [])->assertHasErrors([
            'El nombre del niño es obligatorio.',
            'El apellido del niño es obligatorio.',
            'El número de teléfono del tutor es obligatorio.',
        ]);
    });

    it('rejects over-long free text fields', function () {
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'observations' => str_repeat('a', 501),
        ]))->assertHasErrors(['validation.max.string']);

        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'contact' => ['phone' => str_repeat('9', 21)],
        ]))->assertHasErrors(['validation.max.string']);

        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'contact' => ['international_code' => '+52521'],
        ]))->assertHasErrors(['validation.max.string']);

        expect(Attendance::count())->toBe(0);
    });

    it('rejects a birth date that is not a date', function () {
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'kid' => ['birth_date' => 'ayer'],
        ]))->assertHasErrors(['validation.date']);

        expect(Kid::count())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// handle() — side effects
// ---------------------------------------------------------------------------

describe('handle side effects', function () {
    it('creates the kid, the contact, the link and the attendance', function () {
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        runCreateAttendance(attendancePayload());

        $kid = Kid::sole();
        $contact = Contact::sole();

        expect($kid->first_name)->toBe('Ana')
            ->and($kid->last_name)->toBe('Lopez')
            ->and($kid->gender)->toBe('female')
            ->and($kid->birth_date->toDateString())->toBe('2019-05-04')
            ->and($contact->first_name)->toBe('Rosa')
            ->and($contact->last_name)->toBe('Lopez')
            ->and($contact->phone)->toBe('5512345678')
            ->and($contact->email)->toBe('rosa@example.com')
            ->and($contact->international_code)->toBe('52');

        $this->assertDatabaseHas('contact_kid', [
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'relationship_type' => 'parent',
        ]);

        $attendance = Attendance::sole();

        expect($attendance->kid_id)->toBe($kid->id)
            ->and($attendance->contact_id)->toBe($contact->id)
            ->and($attendance->observations)->toBe('Llego contenta')
            ->and($attendance->check_in)->not->toBeNull();
    });

    it('stores a null observation when none is given', function () {
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        $payload = attendancePayload();
        unset($payload['observations']);

        runCreateAttendance($payload);

        expect(Attendance::sole()->observations)->toBeNull();
    });

    it('reuses an existing kid and contact instead of duplicating them', function () {
        mockTutorMessages()->shouldReceive('sendEntryMessage')->once();

        $kid = Kid::factory()->create([
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'gender' => 'female',
        ]);
        $contact = Contact::factory()->create(['phone' => '5512345678']);
        $kid->contacts()->sync([$contact->id => ['relationship_type' => 'parent']]);

        runCreateAttendance(attendancePayload());

        expect(Kid::where('first_name', 'Ana')->count())->toBe(1)
            ->and(Contact::where('phone', '5512345678')->count())->toBe(1)
            ->and(Attendance::count())->toBe(1);

        // Already linked, so no second pivot row.
        expect($kid->fresh()->contacts)->toHaveCount(1);
    });

    it('links an existing kid to an existing contact that was not linked yet', function () {
        mockTutorMessages()->shouldReceive('sendEntryMessage')->once();

        $kid = Kid::factory()->create([
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'gender' => 'female',
        ]);
        $kid->contacts()->sync([]);
        Contact::factory()->create(['phone' => '5512345678']);

        runCreateAttendance(attendancePayload());

        expect($kid->fresh()->contacts)->toHaveCount(1)
            ->and($kid->fresh()->contacts->first()->phone)->toBe('5512345678')
            ->and($kid->fresh()->contacts->first()->pivot->relationship_type)->toBe('parent');
    });

    it('matches an existing contact by phone only, ignoring the sent name', function () {
        mockTutorMessages()->shouldReceive('sendEntryMessage')->once();

        Kid::factory()->create(['first_name' => 'Ana', 'last_name' => 'Lopez']);
        Contact::factory()->create([
            'phone' => '5512345678', 'first_name' => 'Original', 'last_name' => 'Tutor',
        ]);

        runCreateAttendance(attendancePayload([
            'contact' => ['first_name' => 'Nombre', 'last_name' => 'Nuevo'],
        ]));

        expect(Contact::where('phone', '5512345678')->sole()->first_name)->toBe('Original')
            ->and(Contact::where('phone', '5512345678')->count())->toBe(1);
    });

    it('defaults the international code when the client omits it', function () {
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        $payload = attendancePayload();
        unset($payload['contact']['international_code']);

        runCreateAttendance($payload);

        // KNOWN DEFECT #4 (cosmetic): the tool defaults to '+52' while the DB
        // column default and every factory use '52', so Contact::full_phone
        // renders a doubled plus sign. Pinned so a fix is a deliberate change.
        expect(Contact::sole()->international_code)->toBe('+52')
            ->and(Contact::sole()->full_phone)->toBe('++525512345678');
    });

    it('stores a null email when the client omits it', function () {
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        $payload = attendancePayload();
        unset($payload['contact']['email']);

        runCreateAttendance($payload);

        expect(Contact::sole()->email)->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// handle() — tutor notification branch
// ---------------------------------------------------------------------------

describe('handle notifications', function () {
    it('sends the welcome message when both the kid and the contact are new', function () {
        $service = mockTutorMessages();
        $service->shouldReceive('sendWelcomeMessage')->once()
            ->withArgs(fn (Contact $contact, Kid $kid) => $contact->phone === '5512345678' && $kid->first_name === 'Ana');
        $service->shouldNotReceive('sendEntryMessage');

        runCreateAttendance(attendancePayload());
    });

    it('sends the entry message when the kid already existed', function () {
        $service = mockTutorMessages();
        $service->shouldReceive('sendEntryMessage')->once();
        $service->shouldNotReceive('sendWelcomeMessage');

        Kid::factory()->create(['first_name' => 'Ana', 'last_name' => 'Lopez']);

        runCreateAttendance(attendancePayload());
    });

    it('sends the entry message when only the contact already existed', function () {
        $service = mockTutorMessages();
        $service->shouldReceive('sendEntryMessage')->once();
        $service->shouldNotReceive('sendWelcomeMessage');

        Contact::factory()->create(['phone' => '5512345678']);

        runCreateAttendance(attendancePayload());
    });
});

// ---------------------------------------------------------------------------
// handle() — response
// ---------------------------------------------------------------------------

describe('handle response', function () {
    // REGRESSION GUARD: handle() used to call `Response::success()`, which does
    // not exist on Laravel\Mcp\Response (^0.5) and is registered by no macro, so
    // Macroable::__callStatic threw BadMethodCallException *after* the
    // attendance had been written and the tutor notified. PHPStan caught it
    // (staticMethod.notFound) and it was baselined instead of fixed.

    it('answers a successful, non-error MCP result', function () {
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        runCreateAttendance(attendancePayload())
            ->assertOk()
            ->assertSee('Asistencia registrada exitosamente');
    });

    it('does not flag the result as an MCP error', function () {
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        expect(createAttendanceResult(attendancePayload())['isError'])->toBeFalse();
    });

    it('carries the message, the attendance id and both records', function () {
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        $structured = createAttendanceResult(attendancePayload())['structuredContent'];

        expect($structured)->toHaveKeys(['message', 'attendance_id', 'kid', 'contact'])
            ->and($structured['message'])->toBe('Asistencia registrada exitosamente')
            ->and($structured['attendance_id'])->toBe(Attendance::sole()->id)
            ->and($structured['kid']['id'])->toBe(Kid::sole()->id)
            ->and($structured['kid']['first_name'])->toBe('Ana')
            ->and($structured['kid']['last_name'])->toBe('Lopez')
            ->and($structured['contact']['id'])->toBe(Contact::sole()->id)
            ->and($structured['contact']['phone'])->toBe('5512345678');
    });

    it('mirrors the structured content as a JSON text block', function () {
        // MCP requires a tool returning structuredContent to also serialise it
        // into a text content block, for clients that only read `content`.
        // Response::structured() does both; Response::json() would only do the
        // text half.
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        $result = createAttendanceResult(attendancePayload());

        expect($result['content'])->toHaveCount(1)
            ->and($result['content'][0]['type'])->toBe('text')
            ->and(json_decode($result['content'][0]['text'], true))
            ->toBe($result['structuredContent']);
    });

    it('answers the same shape when the kid and contact already existed', function () {
        mockTutorMessages()->shouldReceive('sendEntryMessage')->once();

        $kid = Kid::factory()->create(['first_name' => 'Ana', 'last_name' => 'Lopez']);
        $contact = Contact::factory()->create(['phone' => '5512345678']);

        $structured = createAttendanceResult(attendancePayload())['structuredContent'];

        expect($structured['message'])->toBe('Asistencia registrada exitosamente')
            ->and($structured['attendance_id'])->toBe(Attendance::sole()->id)
            ->and($structured['kid']['id'])->toBe($kid->id)
            ->and($structured['contact']['id'])->toBe($contact->id);
    });
});

// ---------------------------------------------------------------------------
// KNOWN DEFECTS — these pin behaviour that is wrong today.
// ---------------------------------------------------------------------------

describe('known defects', function () {
    it('KNOWN DEFECT #2: a new kid without birth_date is rejected with a validation error', function () {
        // kids.birth_date is `$table->date('birth_date')` — NOT NULL — and
        // getAgeAttribute, the graduation logic and the sticker age all depend
        // on it, so the fix makes birth_date REQUIRED in the tool (validation
        // `required` + schema `required()`), NOT the column nullable.
        //
        // Today the tool declares it 'nullable|date' with `?? null`, so an MCP
        // client that trusts the schema and omits the birth date gets a NOT NULL
        // QueryException mid-insert. This test pins the DESIRED behaviour: a
        // clean validation error and nothing written. It stays RED until the
        // tool requires birth_date.
        $payload = attendancePayload();
        unset($payload['kid']['birth_date']);

        CreateAttendanceTestServer::tool(createAttendance::class, $payload)
            ->assertHasErrors(['La fecha de nacimiento del niño es obligatoria.']);

        expect(Kid::count())->toBe(0)
            ->and(Contact::count())->toBe(0)
            ->and(Attendance::count())->toBe(0);
    });

    it('rejects an empty birth_date with the same validation error', function () {
        // Z of Z-O-M-B-I-E: an empty string must be rejected exactly as hard as
        // a missing key — same Spanish message — and must not fall through to a
        // NOT NULL insert. Distinguishes `required` from a laxer `nullable`.
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'kid' => ['birth_date' => ''],
        ]))->assertHasErrors(['La fecha de nacimiento del niño es obligatoria.']);

        expect(Kid::count())->toBe(0)
            ->and(Contact::count())->toBe(0)
            ->and(Attendance::count())->toBe(0);
    });

    it('KNOWN DEFECT #5: createAttendance is registered on AttendanceServer and reachable', function () {
        // routes/ai.php exposes AttendanceServer at /mcp/attendance. Its $tools
        // array now lists the four real tools, so createAttendance is reachable
        // through it (not the scaffolded empty stub it used to be).
        //
        // Reachability is proven with an empty payload: a registered tool answers
        // with its own validation errors, whereas an unregistered one answers
        // 'not found'. The empty payload also keeps the WhatsApp side effect from
        // firing, since handle() never runs.
        $tools = (new ReflectionProperty(AttendanceServer::class, 'tools'))->getDefaultValue();

        expect($tools)->toContain(createAttendance::class);

        AttendanceServer::tool(createAttendance::class, [])
            ->assertHasErrors(['El nombre del niño es obligatorio.']);

        expect(Attendance::count())->toBe(0);
    });

    it('KNOWN DEFECT #3: a new kid without gender is rejected with a validation error', function () {
        // kids.gender is `$table->enum('gender', ['male', 'female'])` — NOT NULL
        // and only two legal values — but the tool declared gender optional with
        // an extra invalid member ('not_specified') and defaulted to it, so
        // omitting gender used to violate the enum/check constraint mid-insert
        // with a QueryException.
        //
        // Twin of KNOWN DEFECT #2: the fix aligns the tool with the column ->
        // gender becomes `required|in:male,female`, schema enum(['male','female'])
        // + required(), and the 'not_specified' default is dropped. This test
        // pins the DESIRED behaviour: a clean validation error and nothing
        // written. It stays RED until the tool requires gender.
        $payload = attendancePayload();
        unset($payload['kid']['gender']);

        CreateAttendanceTestServer::tool(createAttendance::class, $payload)
            ->assertHasErrors(['El género del niño es obligatorio.']);

        expect(Kid::count())->toBe(0)
            ->and(Contact::count())->toBe(0)
            ->and(Attendance::count())->toBe(0);
    });

    it('rejects an explicit not_specified gender, which is no longer a legal value', function () {
        // M/Boundary of Z-O-M-B-I-E: 'not_specified' used to be an accepted enum
        // member; after the fix the column's two values (male|female) are the
        // only legal ones, so an explicit 'not_specified' must be rejected just
        // like the 'alien' case above — and must not reach a NOT NULL/enum insert.
        //
        // The app runs on locale 'es' with no lang/es/validation.php (KNOWN
        // DEFECT #6), so the `in` rule leaks the raw translation key; assert it,
        // consistently with 'rejects a gender outside the declared enum'.
        CreateAttendanceTestServer::tool(createAttendance::class, attendancePayload([
            'kid' => ['gender' => 'not_specified'],
        ]))->assertHasErrors(['validation.in']);

        expect(Kid::count())->toBe(0)
            ->and(Contact::count())->toBe(0)
            ->and(Attendance::count())->toBe(0);
    });
});
