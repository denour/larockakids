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
        ->toContain('Resp. Laura')
        // El apellido del responsable partiría la línea en dos y se comería
        // el alto útil de la etiqueta de 62 mm.
        ->not->toContain('Resp. Laura Ramírez');
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

/**
 * "MAVI CASTRO" salía a 30px, medía 202px de los 216 útiles y en la máquina
 * del salón la fuente rendía apenas más ancha: se partía en dos renglones y
 * empujaba el resto de la etiqueta. El tamaño ahora se calcula con margen.
 */
test('the name shrinks enough to stay on one line, even for the case that failed in print', function (string $first, string $last, int $maxPx) {
    $kid = Kid::factory()->make(['first_name' => $first, 'last_name' => $last]);

    $html = view('components.sticker', ['kid' => $kid])->render();

    preg_match('/class="nombre" style="font-size: (\d+)px"/', $html, $m);
    $px = (int) ($m[1] ?? 0);

    $nombre = trim(strtok($first, ' ').' '.strtok($last, ' '));
    // 0.61 es el ancho por carácter medido en Arial 800 mayúsculas
    $anchoEstimado = mb_strlen($nombre) * $px * 0.61;

    expect($px)->toBeGreaterThan(0)
        ->and($px)->toBeLessThanOrEqual($maxPx)
        ->and($anchoEstimado)->toBeLessThan(216.0);
})->with([
    'el que falló impreso' => ['Mavi', 'Castro', 28],
    'corto, usa el máximo' => ['Ana', 'Gil', 30],
    'largo' => ['Maximiliano', 'Hernández', 16],
    'muy largo' => ['Juan Pablo', 'Villavicencio', 18],
]);

test('the name never wraps to a second line', function () {
    $kid = Kid::factory()->make(['first_name' => 'Mavi', 'last_name' => 'Castro']);

    expect(view('components.sticker', ['kid' => $kid])->render())
        ->toContain('white-space: nowrap');
});
