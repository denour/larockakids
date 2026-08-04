<?php

use App\Support\ScannerMode;

beforeEach(function () {
    config(['kiosk.token' => 'kiosk-testing-token']);
});

function scannerPage(string $mode): Illuminate\Testing\TestResponse
{
    return test()
        ->withCookie(config('kiosk.cookie'), 'kiosk-testing-token')
        ->get(route("scanner.{$mode}"));
}

// =============================================================
// ScannerMode
// =============================================================

test('define exactamente los tres modos del kiosco', function () {
    expect(array_keys(ScannerMode::all()))->toBe(['check-in', 'check-out', 'assistance']);
});

test('cada modo trae todo lo que el layout necesita', function () {
    foreach (ScannerMode::all() as $key => $mode) {
        expect($mode)->toHaveKeys([
            'key', 'title', 'subtitle', 'label', 'accent', 'accent_dark', 'icon', 'route', 'process_route',
        ])->and($mode['key'])->toBe($key)
            ->and($mode['accent'])->toMatch('/^#[0-9a-f]{6}$/')
            ->and($mode['accent_dark'])->toMatch('/^#[0-9a-f]{6}$/');
    }
});

test('cada modo tiene su propio color de acento', function () {
    $accents = array_column(ScannerMode::all(), 'accent');

    expect($accents)->toHaveCount(count(array_unique($accents)));
});

test('others devuelve los otros dos modos, nunca el propio', function () {
    foreach (array_keys(ScannerMode::all()) as $key) {
        $others = ScannerMode::others($key);

        expect($others)->toHaveCount(2)
            ->and(array_column($others, 'key'))->not->toContain($key);
    }
});

test('un modo desconocido revienta en vez de renderizar algo vacío', function () {
    ScannerMode::get('modo-que-no-existe');
})->throws(InvalidArgumentException::class);

test('las rutas de cada modo existen', function () {
    foreach (ScannerMode::all() as $mode) {
        expect(Route::has($mode['route']))->toBeTrue()
            ->and(Route::has($mode['process_route']))->toBeTrue();
    }
});

// =============================================================
// Las tres pantallas comparten layout
// =============================================================

test('las tres pantallas cargan', function (string $mode) {
    scannerPage($mode)->assertOk();
})->with(['check-in', 'check-out', 'assistance']);

test('cada pantalla muestra su título y su subtítulo', function () {
    foreach (ScannerMode::all() as $key => $mode) {
        scannerPage($key)
            ->assertSee($mode['title'])
            ->assertSee($mode['subtitle'], false);
    }
});

test('cada pantalla pinta su color de acento', function () {
    foreach (ScannerMode::all() as $key => $mode) {
        scannerPage($key)->assertSee("--accent: {$mode['accent']}", false);
    }
});

test('cada pantalla ofrece los otros dos modos con su propio color', function () {
    foreach (ScannerMode::all() as $key => $mode) {
        $response = scannerPage($key);

        foreach (ScannerMode::others($key) as $other) {
            $response->assertSee(route($other['route']), false)
                ->assertSee("--btn-accent: {$other['accent']}", false);
        }

        $response->assertDontSee('>'.$mode['label'].'<', false);
    }
});

test('cada pantalla apunta a su propia ruta de proceso', function () {
    foreach (ScannerMode::all() as $key => $mode) {
        // La ruta viaja dentro de un @json, donde las barras salen escapadas.
        $enJson = trim(json_encode(route($mode['process_route'])), '"');

        scannerPage($key)->assertSee("const PROCESS_URL = \"{$enJson}\"", false);
    }
});

test('las tres comparten los mismos bloques de interfaz', function () {
    $comunes = ['toast-container', 'permission-error', 'id="reader"', 'mode-switch', 'spinner'];

    foreach (array_keys(ScannerMode::all()) as $key) {
        $response = scannerPage($key);

        foreach ($comunes as $bloque) {
            $response->assertSee($bloque, false);
        }
    }
});

test('las tres estilan el aviso de graduación, no solo entrada', function () {
    foreach (array_keys(ScannerMode::all()) as $key) {
        scannerPage($key)
            ->assertSee('.toast-warning', false)
            ->assertSee('.toast-warning-strong', false);
    }
});

test('las tres mandan la cookie del kiosco al registrar el escaneo', function () {
    foreach (array_keys(ScannerMode::all()) as $key) {
        scannerPage($key)->assertSee("credentials: 'same-origin'", false);
    }
});

test('el layout ya no duplica estilos por pantalla', function () {
    $vistas = glob(resource_path('views/scanner/*.blade.php'));
    $porArchivo = collect($vistas)->mapWithKeys(fn ($v) => [basename($v) => substr_count(file_get_contents($v), '<style>')]);

    expect($porArchivo->sum())->toBe(1)
        ->and($porArchivo['layout.blade.php'])->toBe(1);
});
