<?php

use App\Enums\Country;
use App\Enums\GradeLevel;
use App\Enums\NotificationChannel;
use App\Enums\SphincterControl;
use App\Http\Middleware\SetLocale;
use App\Models\Allergy;
use App\Models\Kid;
use App\Models\OnboardingSession;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A valid registration payload, so each test only states the field it is exercising.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sofía Ramírez Cruz',
        'birth_date' => now()->subYears(3)->toDateString(),
        'gender' => 'female',
        'grade_level' => GradeLevel::TwoYears->value,
        'guardian_name' => 'María López González',
        'international_code' => '52',
        'phone' => '6641234567',
        'notification_channel' => NotificationChannel::WhatsApp->value,
        'wants_parents_group' => '0',
    ], $overrides);
}

test('the entry screen generates a fresh onboarding session with a 6-digit code', function () {
    $this->get(route('onboarding.entry'))
        ->assertOk()
        ->assertSee('Tu código es');

    $session = OnboardingSession::query()->latest('id')->first();
    expect($session)->not->toBeNull()
        ->and($session->code)->toMatch('/^\d{6}$/')
        ->and($session->status)->toBe('pending');
});

test('the status endpoint reports pending, matched and unknown states', function () {
    $pending = OnboardingSession::factory()->create();
    $this->getJson(route('onboarding.status', $pending->code))
        ->assertOk()
        ->assertJson(['status' => 'pending', 'redirect' => null]);

    $kid = Kid::factory()->create();
    $matched = OnboardingSession::factory()->matched($kid->id, '526641234567')->create();
    $this->getJson(route('onboarding.status', $matched->code))
        ->assertOk()
        ->assertJson([
            'status' => 'matched',
            'redirect' => route('onboarding.confirm', $kid->id),
        ]);

    $this->getJson(route('onboarding.status', '000000'))->assertNotFound();
});

test('the search and registration screens render for staff', function () {
    $this->get(route('onboarding.search'))->assertOk()->assertSee('Busca a tu hijo');
    $this->get(route('onboarding.register', ['name' => 'Sofía Ramírez']))
        ->assertOk()
        ->assertSee('No encontramos a tu hijo')
        ->assertSee('Sofía Ramírez');
});

test('searching a single kid redirects straight to confirmation', function () {
    $kid = Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Hernández López']);

    $this->post(route('onboarding.find'), ['name' => 'Mateo Hernández'])
        ->assertRedirect(route('onboarding.confirm', $kid));
});

test('searching with no results sends the staff to the registration form', function () {
    $this->post(route('onboarding.find'), ['name' => 'Fulano Inexistente'])
        ->assertRedirect(route('onboarding.register', ['name' => 'Fulano Inexistente']));
});

test('searching with several matches returns a picker list', function () {
    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Hernández']);
    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'García']);

    $this->post(route('onboarding.find'), ['name' => 'Mateo'])
        ->assertRedirect(route('onboarding.search'))
        ->assertSessionHas('matches');
});

test('searching requires a name', function () {
    $this->post(route('onboarding.find'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('registering a new kid persists all onboarding data and links the guardian', function () {
    $allergy = Allergy::factory()->create();

    $payload = [
        'name' => 'Sofía Ramírez Cruz',
        'birth_date' => '2022-05-11',
        'gender' => 'female',
        'grade_level' => GradeLevel::TwoYears->value,
        'classroom' => 'Salón B',
        'allergy_id' => $allergy->id,
        'medical_conditions' => 'Asma leve',
        'medications' => 'Ninguno',
        'sphincter_control' => SphincterControl::DaytimeControl->value,
        'nap' => 'yes',
        'routine_notes' => 'Duerme después de comer',
        'guardian_name' => 'María López González',
        'international_code' => '52',
        'phone' => '6641234567',
        'wants_parents_group' => '1',
        'notification_channel' => NotificationChannel::WhatsApp->value,
    ];

    $kid = null;
    $this->post(route('onboarding.store'), $payload)
        ->assertRedirect();

    $kid = Kid::query()->where('first_name', 'Sofía')->first();
    expect($kid)->not->toBeNull()
        ->and($kid->last_name)->toBe('Ramírez Cruz')
        ->and($kid->grade_level)->toBe(GradeLevel::TwoYears)
        ->and($kid->sphincter_control)->toBe(SphincterControl::DaytimeControl)
        ->and($kid->notification_channel)->toBe(NotificationChannel::WhatsApp)
        ->and($kid->wants_parents_group)->toBeTrue()
        ->and($kid->classroom)->toBe('Salón B')
        ->and($kid->school_cycle)->not->toBeNull();

    expect($kid->allergies)->toHaveCount(1);

    $contact = $kid->contacts->first();
    expect($contact)->not->toBeNull()
        ->and($contact->first_name)->toBe('María')
        ->and($contact->last_name)->toBe('López González')
        ->and($contact->phone)->toBe('6641234567');
});

test('registration validates the required fields', function () {
    $this->post(route('onboarding.store'), ['name' => ''])
        ->assertSessionHasErrors(['name', 'birth_date', 'gender', 'grade_level', 'guardian_name', 'phone', 'notification_channel']);
});

/**
 * The app ships no lang/<locale>/validation.php, so any rule without an inline message
 * used to render its raw key ("validation.required") straight into the kiosk.
 */
test('no validation message leaks a raw translation key to the parent', function (string $locale) {
    session([SetLocale::SESSION_KEY => $locale]);

    // Trips the required rules and the non-required ones (enum, in, max, date, exists, boolean).
    $this->post(route('onboarding.store'), [
        'name' => str_repeat('a', 300),
        'birth_date' => 'not-a-date',
        'gender' => 'martian',
        'grade_level' => 'not-a-grade',
        'sphincter_control' => 'nope',
        'nap' => 'nope',
        'allergy_id' => 999999,
        'international_code' => '999',
        'wants_parents_group' => 'maybe',
        'notification_channel' => 'carrier-pigeon',
        'classroom' => str_repeat('a', 300),
    ]);

    $messages = collect(session('errors')->getBag('default')->all());

    expect($messages)->not->toBeEmpty()
        ->and($messages->filter(fn (string $m): bool => str_contains($m, 'validation.'))->all())->toBe([]);
})->with(['es', 'en']);

test('the gender field is rejected with friendly copy in both languages', function () {
    session([SetLocale::SESSION_KEY => 'es']);
    $this->post(route('onboarding.store'), registrationPayload(['gender' => '']))
        ->assertSessionHasErrors(['gender' => 'Selecciona el género.']);

    session([SetLocale::SESSION_KEY => 'en']);
    $this->post(route('onboarding.store'), registrationPayload(['gender' => '']))
        ->assertSessionHasErrors(['gender' => 'Pick the gender.']);
});

test('editing surfaces no raw translation key either', function () {
    $kid = Kid::factory()->create();

    session([SetLocale::SESSION_KEY => 'es']);
    $this->put(route('onboarding.update', $kid), ['name' => '', 'gender' => 'martian']);

    $messages = collect(session('errors')->getBag('default')->all());

    expect($messages)->not->toBeEmpty()
        ->and($messages->filter(fn (string $m): bool => str_contains($m, 'validation.'))->all())->toBe([]);
});

test('registration rejects a birth date older than the 4-year limit', function () {
    $this->post(route('onboarding.store'), registrationPayload([
        'birth_date' => now()->subYears(6)->toDateString(),
    ]))->assertSessionHasErrors(['birth_date']);

    expect(Kid::query()->count())->toBe(0);
});

test('registration rejects a birth date in the future', function () {
    $this->post(route('onboarding.store'), registrationPayload([
        'birth_date' => now()->addDay()->toDateString(),
    ]))->assertSessionHasErrors(['birth_date']);

    expect(Kid::query()->count())->toBe(0);
});

test('registration accepts a birth date of a three year old', function () {
    $this->post(route('onboarding.store'), registrationPayload([
        'birth_date' => now()->subYears(3)->toDateString(),
    ]))->assertSessionHasNoErrors()->assertRedirect();

    expect(Kid::query()->count())->toBe(1);
});

test('registration rejects a dialing code that is not one of the offered countries', function () {
    $this->post(route('onboarding.store'), registrationPayload([
        'international_code' => '999',
    ]))->assertSessionHasErrors(['international_code']);
});

test('registration keeps the dialing code picked in the country dropdown', function () {
    $this->post(route('onboarding.store'), registrationPayload([
        'international_code' => Country::COLOMBIA->getCode(),
    ]))->assertSessionHasNoErrors();

    expect(Kid::query()->first()->contacts->first()->international_code)->toBe('57');
});

test('the registration form offers a real country dropdown and clamps the birth date', function () {
    $this->get(route('onboarding.register'))
        ->assertOk()
        ->assertSee('name="international_code"', false)
        ->assertSee('min="'.now()->subYears(5)->addDay()->toDateString().'"', false)
        ->assertSee('max="'.now()->toDateString().'"', false);
});

test('editing rejects a birth date older than the 4-year limit', function () {
    ['kid' => $kid] = createKidWithContact();

    $this->put(route('onboarding.update', $kid), [
        'name' => 'Mateo Hernández López',
        'birth_date' => now()->subYears(6)->toDateString(),
        'gender' => 'male',
        'grade_level' => GradeLevel::ThreeYears->value,
        'guardian_name' => 'María López González',
        'international_code' => '52',
        'phone' => '6649998888',
        'notification_channel' => NotificationChannel::Screen->value,
        'wants_parents_group' => '0',
    ])->assertSessionHasErrors(['birth_date']);
});

test('the confirmation screen shows the registered information', function () {
    ['kid' => $kid] = createKidWithContact([
        'first_name' => 'Mateo',
        'last_name' => 'Hernández López',
        'grade_level' => GradeLevel::FourYears->value,
    ]);

    $this->get(route('onboarding.confirm', $kid))
        ->assertOk()
        ->assertSee('Mateo Hernández López')
        ->assertSee('¡Encontramos a tu hijo!');
});

test('editing updates the kid and the guardian contact', function () {
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(
        ['first_name' => 'Mateo', 'last_name' => 'Hernández'],
        ['first_name' => 'María', 'last_name' => 'López', 'phone' => '6640000000', 'international_code' => '52'],
    );

    $this->get(route('onboarding.edit', $kid))->assertOk()->assertSee('Editar información de tu hijo');

    $this->put(route('onboarding.update', $kid), [
        'name' => 'Mateo Hernández López',
        'birth_date' => now()->subYears(3)->toDateString(),
        'gender' => 'male',
        'grade_level' => GradeLevel::ThreeYears->value,
        'guardian_name' => 'María López González',
        'international_code' => '52',
        'phone' => '6649998888',
        'notification_channel' => NotificationChannel::Screen->value,
        'wants_parents_group' => '0',
    ])->assertRedirect(route('onboarding.confirm', $kid));

    $kid->refresh();
    expect($kid->last_name)->toBe('Hernández López')
        ->and($kid->grade_level)->toBe(GradeLevel::ThreeYears)
        ->and($kid->wants_parents_group)->toBeFalse();

    expect($kid->contacts->first()->phone)->toBe('6649998888');
});

test('the done screen celebrates a normal registration', function () {
    $kid = Kid::factory()->create(['grade_level' => GradeLevel::TwoYears->value]);

    $this->get(route('onboarding.done', $kid))
        ->assertOk()
        ->assertSee('¡Proceso terminado!');
});

test('a kid in the final grade sees the graduation screen instead of the done screen', function () {
    $kid = Kid::factory()->graduating()->create();

    $this->get(route('onboarding.done', $kid))
        ->assertOk()
        ->assertSee('graduarse');
});

test('the age rejection is explained in spanish on the form', function () {
    $this->from(route('onboarding.register'))
        ->post(route('onboarding.store'), registrationPayload([
            'birth_date' => now()->subYears(7)->toDateString(),
        ]))
        ->assertRedirect(route('onboarding.register'))
        ->assertSessionHasErrors([
            'birth_date' => __('onboarding.validation.birth_date_too_old', [], 'es'),
        ]);

    $this->followingRedirects()
        ->from(route('onboarding.register'))
        ->post(route('onboarding.store'), registrationPayload([
            'birth_date' => now()->subYears(7)->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('debe corresponder a un niño de máximo 4 años', false);
});

test('the age rejection is explained in english when the kiosk runs in english', function () {
    $this->withSession([SetLocale::SESSION_KEY => 'en'])
        ->post(route('onboarding.store'), registrationPayload([
            'birth_date' => now()->subYears(7)->toDateString(),
        ]))
        ->assertSessionHasErrors([
            'birth_date' => __('onboarding.validation.birth_date_too_old', [], 'en'),
        ]);
});

test('every offered country can be picked and is stored on the contact', function (string $code) {
    $this->post(route('onboarding.store'), registrationPayload([
        'name' => 'Kid '.$code,
        'international_code' => $code,
    ]))->assertSessionHasNoErrors();

    expect(Kid::query()->latest('id')->first()->contacts->first()->international_code)->toBe($code);
})->with(fn () => collect(Country::cases())->map->getCode()->unique()->values()->all());

test('the edit form preselects the stored country and keeps a new one', function () {
    ['kid' => $kid] = createKidWithContact([], ['international_code' => '57', 'phone' => '3001234567']);

    $this->get(route('onboarding.edit', $kid))
        ->assertOk()
        ->assertSee('<option value="57" title="Colombia" selected', false);

    $this->put(route('onboarding.update', $kid), [
        'name' => 'Mateo Hernández López',
        'birth_date' => now()->subYears(3)->toDateString(),
        'gender' => 'male',
        'grade_level' => GradeLevel::ThreeYears->value,
        'guardian_name' => 'María López González',
        'international_code' => '34',
        'phone' => '600112233',
        'notification_channel' => NotificationChannel::Screen->value,
        'wants_parents_group' => '0',
    ])->assertSessionHasNoErrors();

    expect($kid->fresh()->contacts->first()->international_code)->toBe('34');
});

test('the form action bar sits in the document flow so it can never cover a field', function () {
    ['kid' => $kid] = createKidWithContact();

    foreach ([route('onboarding.register'), route('onboarding.edit', $kid)] as $url) {
        $this->get($url)->assertOk()->assertDontSee('fixed bottom-0', false);
    }
});

test('the birth date input carries a localised message for the native range bubble', function () {
    // Without this the browser shows its own English "Value must be ... or later".
    $this->get(route('onboarding.register'))
        ->assertOk()
        ->assertSee('data-range-message="'.e(__('onboarding.validation.birth_date_too_old', [], 'es')).'"', false);

    $this->withSession([SetLocale::SESSION_KEY => 'en'])
        ->get(route('onboarding.register'))
        ->assertOk()
        ->assertSee('data-range-message="'.e(__('onboarding.validation.birth_date_too_old', [], 'en')).'"', false);
});

test('the parents group toggle starts on when registering and mirrors the record when editing', function () {
    $this->get(route('onboarding.register'))
        ->assertOk()
        ->assertSee('name="wants_parents_group" value="1" checked', false);

    ['kid' => $optedOut] = createKidWithContact(['wants_parents_group' => false]);
    $this->get(route('onboarding.edit', $optedOut))
        ->assertOk()
        ->assertDontSee('name="wants_parents_group" value="1" checked', false);

    ['kid' => $optedIn] = createKidWithContact(['wants_parents_group' => true]);
    $this->get(route('onboarding.edit', $optedIn))
        ->assertOk()
        ->assertSee('name="wants_parents_group" value="1" checked', false);
});

/** The register and edit mockups both show "Por WhatsApp" already picked. */
test('the notification channel starts on whatsapp when registering and mirrors the record when editing', function () {
    $this->get(route('onboarding.register'))
        ->assertOk()
        ->assertSee('name="notification_channel" value="whatsapp" checked', false)
        ->assertDontSee('name="notification_channel" value="screen" checked', false);

    ['kid' => $onScreen] = createKidWithContact(['notification_channel' => NotificationChannel::Screen->value]);
    $this->get(route('onboarding.edit', $onScreen))
        ->assertOk()
        ->assertSee('name="notification_channel" value="screen" checked', false)
        ->assertDontSee('name="notification_channel" value="whatsapp" checked', false);
});

test('the grade is shown with the preschool naming from the mockups, never as an age', function () {
    ['kid' => $kid] = createKidWithContact(['grade_level' => GradeLevel::TwoYears->value]);

    $this->get(route('onboarding.confirm', $kid))
        ->assertOk()
        ->assertSee('Preescolar 2')
        ->assertDontSee('Grado escolar: 2 años');

    $this->get(route('onboarding.edit', $kid))
        ->assertOk()
        ->assertSee('<option value="2" selected>Preescolar 2</option>', false);
});

test('every kiosk screen keeps a consistent card width', function () {
    ['kid' => $kid] = createKidWithContact();
    $graduating = Kid::factory()->graduating()->create();

    // Single-card screens share one width; the two-column forms share the wider one.
    foreach ([
        route('onboarding.entry') => 'max-w-6xl',
        route('onboarding.search') => 'max-w-6xl',
        route('onboarding.confirm', $kid) => 'max-w-6xl',
        route('onboarding.done', $kid) => 'max-w-6xl',
        route('onboarding.done', $graduating) => 'max-w-6xl',
        route('onboarding.register') => 'max-w-7xl',
        route('onboarding.edit', $kid) => 'max-w-7xl',
    ] as $url => $width) {
        $this->get($url)->assertOk()->assertSee($width, false);
    }
});

test('the splash screen shows the wordmark and leads into the kiosk', function () {
    $this->get(route('onboarding.splash'))
        ->assertOk()
        ->assertSee(__('onboarding.splash.tagline'))
        ->assertSee(route('onboarding.entry'), false);
});
