<?php

use App\Enums\ServiceTime;
use App\Models\Attendance;
use App\Models\Kid;

test('sticker view renders name, phone and reunion for a kid with contact', function () {
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(
        kidData: ['first_name' => 'Sofía', 'last_name' => 'Ramírez'],
        contactData: ['first_name' => 'Laura', 'last_name' => 'Ramírez', 'phone' => '5512345678', 'international_code' => '52'],
    );

    $attendance = Attendance::factory()->create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'service' => ServiceTime::First,
    ]);

    $html = view('components.sticker', [
        'kid' => $kid,
        'contact' => $contact,
        'attendance' => $attendance,
    ])->render();

    expect($html)
        ->toContain('Sofía Ramírez')
        ->toContain($contact->full_phone)
        ->toContain(ServiceTime::First->getShortLabel())
        ->toContain('Resp. Laura Ramírez');
});

test('sticker view renders without breaking when kid has no phone, allergies or attendance', function () {
    $kid = Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'López']);
    $kid->contacts()->sync([]);
    $kid->allergies()->sync([]);

    $html = view('components.sticker', [
        'kid' => $kid,
    ])->render();

    expect($html)
        ->toContain('Mateo López')
        ->not->toContain('<div class="telbox">')
        ->not->toContain('<div class="reunion">')
        ->not->toContain('<div class="alerta">')
        ->not->toContain('TELÉFONO')
        ->not->toContain('ALERGIA');
});

test('the sticker shows one given name and one surname, never the full legal name', function () {
    $kid = Kid::factory()->create([
        'first_name' => 'Mateo Andrés',
        'last_name' => 'Hernández López',
    ]);

    $html = view('components.sticker', ['kid' => $kid])->render();

    expect($html)->toContain('Mateo Hernández')
        ->and($html)->not->toContain('Andrés')
        ->and($html)->not->toContain('López');
});
