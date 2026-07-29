<?php

use App\Mcp\Servers\AttendanceServer;
use App\Mcp\Tools\createAttendance;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Services\TutorMessageService;
use Illuminate\Contracts\JsonSchema\JsonSchema as JsonSchemaContract;
use Illuminate\Database\QueryException;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
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
| Coverage: 70/70 statements, 2/2 methods.
| Mutation: 55/60 killed (91.67%) via
|   vendor/bin/pest tests/Feature/Mcp/CreateAttendanceToolTest.php \
|     --mutate --class="App\Mcp\Tools\createAttendance"
| The 5 survivors are not killable as the code stands:
|   - line 73, dropping ['relationship_type' => 'parent'] from attach(): the
|     contact_kid.relationship_type column defaults to 'parent', so the mutant
|     is behaviourally equivalent.
|   - lines 92-95, dropping any key from the Response::success() payload: that
|     array is never read, because the call itself throws (KNOWN DEFECT #1).
|     Fixing #1 makes all four killable.
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
 * Calls the tool through the MCP server and returns whatever it died with.
 *
 * handle() currently always dies on `Response::success()` (see KNOWN DEFECT #1),
 * but every side effect before that line has already happened, so the callers
 * below can still assert on the database.
 *
 * @param  array<string, mixed>  $arguments
 */
function runCreateAttendance(array $arguments): ?Throwable
{
    try {
        CreateAttendanceTestServer::tool(createAttendance::class, $arguments);

        return null;
    } catch (Throwable $throwable) {
        return $throwable;
    }
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
            ->and($gender['enum'])->toBe(['male', 'female', 'not_specified'])
            ->and($gender['description'])->toBe('Género del niño');
    });

    it('marks the kid name fields as required and the rest as optional', function () {
        $kid = (new createAttendance)->toArray()['inputSchema']['properties']['kid'];

        expect($kid['type'])->toBe('object')
            ->and($kid['required'])->toBe(['first_name', 'last_name'])
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
// KNOWN DEFECTS — these pin behaviour that is wrong today.
// ---------------------------------------------------------------------------

describe('known defects', function () {
    it('KNOWN DEFECT #1: dies on Response::success(), which does not exist', function () {
        // `Laravel\Mcp\Response` has no success() method (laravel/mcp ^0.5) and
        // no macro registers one, so Macroable::__callStatic throws. Every
        // successful call to this tool dies AFTER writing to the database —
        // the attendance is saved and the tutor is notified, but the MCP client
        // gets a 500. PHPStan found this (staticMethod.notFound) and it was
        // baselined instead of fixed; the tool is also not registered in
        // AttendanceServer::$tools, which is why nobody has hit it yet.
        //
        // EXPECTED: return a real Response (e.g. Response::text(...)) carrying
        // the message, attendance_id, kid and contact. When that lands, replace
        // this test with an assertion on the response payload.
        mockTutorMessages()->shouldReceive('sendWelcomeMessage')->once();

        $throwable = runCreateAttendance(attendancePayload());

        expect($throwable)->toBeInstanceOf(BadMethodCallException::class)
            ->and($throwable->getMessage())
            ->toBe('Method Laravel\Mcp\Response::success does not exist.');

        // The side effects were already committed before the throw.
        expect(Attendance::count())->toBe(1);
    });

    it('KNOWN DEFECT #2: a new kid without birth_date violates the NOT NULL column', function () {
        // The tool declares kid.birth_date as optional ('nullable|date' and
        // `?? null`), but kids.birth_date is `$table->date('birth_date')` —
        // NOT NULL. Any MCP client that trusts the schema and omits the birth
        // date gets an integrity constraint violation.
        //
        // EXPECTED: either require birth_date in the tool, or make the column
        // nullable. That is a live-schema decision, not a test decision.
        $payload = attendancePayload();
        unset($payload['kid']['birth_date']);

        $throwable = runCreateAttendance($payload);

        expect($throwable)->toBeInstanceOf(QueryException::class)
            ->and($throwable->getMessage())->toContain('NOT NULL constraint failed: kids.birth_date');

        expect(Kid::count())->toBe(0)
            ->and(Attendance::count())->toBe(0);
    });

    it('KNOWN DEFECT #5: AttendanceServer does not register the tool at all', function () {
        // routes/ai.php exposes AttendanceServer at /mcp/attendance, but its
        // $tools array is still the scaffolded empty stub, so no MCP client can
        // reach createAttendance. That is why defects #1-#3 have never been hit
        // in production, and why these tests need a fixture server.
        //
        // EXPECTED: createAttendance::class listed in AttendanceServer::$tools.
        // When that lands, point this test (and the fixture) at AttendanceServer.
        $tools = (new ReflectionProperty(AttendanceServer::class, 'tools'))->getDefaultValue();

        expect($tools)->toBe([]);

        AttendanceServer::tool(createAttendance::class, attendancePayload())
            ->assertHasErrors(['not found']);

        expect(Attendance::count())->toBe(0);
    });

    it('KNOWN DEFECT #3: the not_specified gender default is not a valid column value', function () {
        // The tool validates gender against male|female|not_specified and
        // defaults to 'not_specified', but kids.gender is
        // `$table->enum('gender', ['male', 'female'])`. Omitting gender for a
        // new kid violates the enum/check constraint.
        //
        // EXPECTED: align the tool with the column (default 'male') or widen
        // the column. Again a live-schema decision.
        $payload = attendancePayload();
        unset($payload['kid']['gender']);

        $throwable = runCreateAttendance($payload);

        expect($throwable)->toBeInstanceOf(QueryException::class)
            ->and($throwable->getMessage())->toContain('gender');

        expect(Kid::count())->toBe(0);
    });
});
