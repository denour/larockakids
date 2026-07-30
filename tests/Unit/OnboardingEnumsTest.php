<?php

use App\Enums\Country;
use App\Enums\GradeLevel;
use App\Enums\NapPreference;
use App\Enums\NotificationChannel;
use App\Enums\SphincterControl;

/** The enum labels resolve through the translator, so this file needs the app booted. */
uses(Tests\TestCase::class);

test('GradeLevel suggests a grade from the kid age', function () {
    expect(GradeLevel::fromAge(0))->toBe(GradeLevel::OneYear)
        ->and(GradeLevel::fromAge(1))->toBe(GradeLevel::OneYear)
        ->and(GradeLevel::fromAge(2))->toBe(GradeLevel::TwoYears)
        ->and(GradeLevel::fromAge(3))->toBe(GradeLevel::ThreeYears)
        ->and(GradeLevel::fromAge(4))->toBe(GradeLevel::FourYears)
        ->and(GradeLevel::fromAge(6))->toBe(GradeLevel::FourYears);
});

test('only the four-year grade is the final one', function () {
    expect(GradeLevel::FourYears->isFinal())->toBeTrue()
        ->and(GradeLevel::ThreeYears->isFinal())->toBeFalse();
});

test('grade level exposes labelled options', function () {
    expect(GradeLevel::options())->toBe([
        '1' => 'Preescolar 1',
        '2' => 'Preescolar 2',
        '3' => 'Preescolar 3',
        '4' => 'Preescolar 4',
    ]);
});

test('the enum labels switch with the active locale', function () {
    app()->setLocale('en');

    expect(GradeLevel::options())->toBe([
        '1' => 'Preschool 1',
        '2' => 'Preschool 2',
        '3' => 'Preschool 3',
        '4' => 'Preschool 4',
    ])
        ->and(SphincterControl::DaytimeControl->getLabel())->toBe('Daytime control')
        ->and(NapPreference::Sometimes->getLabel())->toBe('Sometimes')
        ->and(NotificationChannel::WhatsApp->getLabel())->toBe('On WhatsApp');
});

test('the habit and notification enums expose labels and options', function () {
    expect(SphincterControl::DaytimeControl->getLabel())->toBe('Controla durante el día')
        ->and(SphincterControl::options())->toHaveCount(4)
        ->and(NapPreference::Yes->getLabel())->toBe('Sí')
        ->and(NapPreference::options())->toHaveCount(3)
        ->and(NotificationChannel::WhatsApp->getLabel())->toBe('Por WhatsApp')
        ->and(NotificationChannel::Screen->getLabel())->toBe('Por la pantalla del escenario')
        ->and(NotificationChannel::options())->toHaveCount(2);
});

test('every offered country exposes a dialling code, a flag and a name', function () {
    foreach (Country::cases() as $country) {
        expect($country->getCode())->toMatch('/^\d{1,3}$/')
            ->and($country->getFlag())->toBe('flag-'.strtolower($country->value))
            ->and($country->getName())->not->toBeEmpty();
    }

    expect(Country::getDefaultCountry())->toBe(Country::MEXICO)
        ->and(Country::MEXICO->getCode())->toBe('52');
});

test('the country select array pairs every case with its name and code', function () {
    $options = Country::toSelectArray();

    expect($options)->toHaveCount(count(Country::cases()))
        ->and($options['MX'])->toBe('México (+52)')
        ->and($options['EC'])->toBe('Ecuador (+593)');
});
