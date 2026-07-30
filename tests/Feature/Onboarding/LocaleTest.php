<?php

use App\Enums\GradeLevel;
use App\Enums\NapPreference;
use App\Enums\NotificationChannel;
use App\Enums\SphincterControl;
use App\Http\Middleware\SetLocale;
use App\Models\Kid;
use App\Models\User;
use Illuminate\Support\Facades\App;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('switching the locale stores it in the session and bounces back', function () {
    $this->from(route('onboarding.entry'))
        ->get(route('onboarding.locale', 'en'))
        ->assertRedirect(route('onboarding.entry'))
        ->assertSessionHas(SetLocale::SESSION_KEY, 'en');
});

test('the stored locale is applied to the next request', function () {
    $this->withSession([SetLocale::SESSION_KEY => 'en'])
        ->get(route('onboarding.entry'))
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee('aria-label="Language"', false);
});

test('spanish stays the default when no locale was picked', function () {
    $this->get(route('onboarding.entry'))
        ->assertOk()
        ->assertSee('<html lang="es"', false)
        ->assertSee('aria-label="Idioma"', false);
});

test('the language switcher renders both options and marks the active one', function () {
    $this->get(route('onboarding.entry'))
        ->assertOk()
        ->assertSee(route('onboarding.locale', 'es'))
        ->assertSee(route('onboarding.locale', 'en'))
        ->assertSee('English');
});

test('an unsupported locale is rejected by the route whitelist', function () {
    $this->get('/onboarding/locale/fr')->assertNotFound();
});

test('the spanish and english onboarding translations share the exact same keys', function () {
    $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
        $keys = [];
        foreach ($items as $key => $value) {
            $keys = array_merge($keys, is_array($value) ? $flatten($value, $prefix.$key.'.') : [$prefix.$key]);
        }

        return $keys;
    };

    $es = $flatten(require lang_path('es/onboarding.php'));
    $en = $flatten(require lang_path('en/onboarding.php'));

    sort($es);
    sort($en);

    expect($en)->toBe($es)->and($es)->not->toBeEmpty();
});

test('the onboarding brand assets and self-hosted font are present', function () {
    foreach ([
        'images/onboarding/logo-piedritas-kids.png',
        'images/onboarding/kid-graduation.png',
        'images/onboarding/kid-done.png',
        'fonts/baloo2-latin.woff2',
        'fonts/baloo2-latin-ext.woff2',
    ] as $asset) {
        expect(public_path($asset))->toBeFile();
    }
});

test('every kiosk screen is translated end to end when the locale is english', function () {
    ['kid' => $kid] = createKidWithContact([
        'first_name' => 'Mateo',
        'last_name' => 'Hernández López',
        'grade_level' => GradeLevel::TwoYears->value,
        'sphincter_control' => SphincterControl::DaytimeControl->value,
    ]);
    $graduating = Kid::factory()->graduating()->create();

    $screens = [
        route('onboarding.entry') => ['We’ll find your children', 'Encontraremos'],
        route('onboarding.search') => ['Find your child', 'Busca a tu hijo'],
        route('onboarding.register') => ['We couldn’t find your child', 'No encontramos'],
        route('onboarding.confirm', $kid) => ['We found your child!', '¡Encontramos'],
        route('onboarding.edit', $kid) => ['Edit your child’s information', 'Editar información'],
        route('onboarding.done', $kid) => ['All done!', '¡Proceso terminado!'],
        route('onboarding.done', $graduating) => ['about to graduate', 'graduarse'],
    ];

    foreach ($screens as $url => [$english, $spanish]) {
        $this->withSession([SetLocale::SESSION_KEY => 'en'])
            ->get($url)
            ->assertOk()
            ->assertSee($english, false)
            ->assertDontSee($spanish, false);
    }
});

test('enum labels follow the active locale instead of staying in spanish', function () {
    expect(GradeLevel::TwoYears->getLabel())->toBe('Preescolar 2')
        ->and(SphincterControl::DaytimeControl->getLabel())->toBe('Controla durante el día');

    App::setLocale('en');

    expect(GradeLevel::TwoYears->getLabel())->toBe('Preschool 2')
        ->and(SphincterControl::DaytimeControl->getLabel())->toBe('Daytime control')
        ->and(NapPreference::Sometimes->getLabel())->toBe('Sometimes')
        ->and(NotificationChannel::Screen->getLabel())->toBe('On the stage screen');
});

test('the confirmation screen renders english enum labels, not spanish ones', function () {
    ['kid' => $kid] = createKidWithContact([
        'grade_level' => GradeLevel::TwoYears->value,
        'sphincter_control' => SphincterControl::DaytimeControl->value,
    ]);

    $this->withSession([SetLocale::SESSION_KEY => 'en'])
        ->get(route('onboarding.confirm', $kid))
        ->assertOk()
        ->assertSee('Daytime control')
        ->assertSee('Preschool 2')
        ->assertDontSee('Controla durante el día')
        ->assertDontSee('Preescolar 2');
});

test('the language switcher is reachable from every screen that shows the header', function () {
    ['kid' => $kid] = createKidWithContact();

    foreach ([
        route('onboarding.entry'),
        route('onboarding.search'),
        route('onboarding.register'),
        route('onboarding.edit', $kid),
    ] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee(route('onboarding.locale', 'en'))
            ->assertSee(route('onboarding.locale', 'es'));
    }
});
