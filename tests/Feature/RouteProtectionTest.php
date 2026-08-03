<?php

use App\Models\Kid;
use App\Models\QrCode;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['kiosk.token' => 'kiosk-testing-token']);
});

// =============================================================
// Rutas administrativas: exigen sesión
// =============================================================

test('el export de niños rechaza a un visitante anónimo', function () {
    $this->get(route('export.kids'))->assertRedirect('/admin/login');
});

test('el export de niños funciona para un usuario autenticado', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('export.kids'))
        ->assertOk();
});

test('la impresión de un gafete rechaza a un visitante anónimo', function () {
    $qrCode = QrCode::factory()->create();

    $this->get(route('qr-codes.print', $qrCode))->assertRedirect('/admin/login');
});

test('la impresión por lote rechaza a un visitante anónimo', function () {
    $qrCodes = QrCode::factory()->count(2)->create();

    $this->get(route('qr-codes.print-batch', ['ids' => $qrCodes->pluck('id')->join(',')]))
        ->assertRedirect('/admin/login');
});

test('el disparador de notificación de prueba rechaza a un visitante anónimo', function () {
    $this->post(route('test.notification'))->assertRedirect('/admin/login');
});

test('la vista de monitoreo de whatsapp rechaza a un visitante anónimo', function () {
    $this->get(route('whatsapp'))->assertRedirect('/admin/login');
});

// =============================================================
// Kiosco: exige token de tablet o sesión del personal
// =============================================================

test('el escáner rechaza a quien no trae el token del kiosco', function () {
    $this->get(route('scanner.check-in'))->assertForbidden();
});

test('el escáner rechaza un token del kiosco incorrecto', function () {
    $this->withCookie(config('kiosk.cookie'), 'token-equivocado')
        ->get(route('scanner.check-in'))
        ->assertForbidden();
});

test('el escáner queda cerrado si no hay token configurado', function () {
    config(['kiosk.token' => null]);

    $this->withCookie(config('kiosk.cookie'), 'kiosk-testing-token')
        ->get(route('scanner.check-in'))
        ->assertForbidden();
});

test('el registro de asistencia rechaza un POST anónimo', function () {
    $kid = Kid::factory()->create();
    createAssignedQr($kid, 'PROT-0001');

    $this->postJson(route('scanner.check-in.process'), ['code' => 'PROT-0001'])
        ->assertForbidden();

    $this->assertDatabaseCount('attendances', 0);
});

test('la tablet se autoriza con el token en la URL y guarda la cookie', function () {
    $response = $this->get(route('scanner.check-in', ['kiosk_token' => 'kiosk-testing-token']));

    $response->assertRedirect(route('scanner.check-in'))
        ->assertCookie(config('kiosk.cookie'), 'kiosk-testing-token');
});

test('la tablet ya autorizada entra al escáner con su cookie', function () {
    $this->withCookie(config('kiosk.cookie'), 'kiosk-testing-token')
        ->get(route('scanner.check-in'))
        ->assertOk();
});

test('el personal autenticado entra al escáner sin token de kiosco', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('scanner.check-in'))
        ->assertOk();
});

// =============================================================
// Códigos nuevos: no se pueden adivinar contando
// =============================================================

test('los códigos nuevos llevan sufijo aleatorio', function () {
    Storage::fake('public');

    $codes = app(QrCodeService::class)->generateBatch(3, 'RND');

    foreach ($codes as $code) {
        expect($code->code)->toMatch('/^RND-\d{4}-[A-Z2-9]{6}$/');
    }
});

test('dos lotes seguidos no repiten el sufijo', function () {
    Storage::fake('public');

    $service = app(QrCodeService::class);
    $suffixes = $service->generateBatch(10, 'UNQ')
        ->map(fn (QrCode $qr) => substr($qr->code, -6))
        ->unique();

    expect($suffixes)->toHaveCount(10);
});

test('el sufijo omite caracteres que se confunden al leerlos', function () {
    Storage::fake('public');

    $codes = app(QrCodeService::class)->generateBatch(20, 'CLR');

    foreach ($codes as $code) {
        expect(substr($code->code, -6))->not->toMatch('/[01IO]/');
    }
});

test('los gafetes viejos sin sufijo siguen sirviendo en el escáner', function () {
    $kid = Kid::factory()->create();
    createAssignedQr($kid, 'LRK-0001');

    $this->withCredentials()
        ->withCookie(config('kiosk.cookie'), 'kiosk-testing-token')
        ->postJson(route('scanner.check-in.process'), ['code' => 'LRK-0001'])
        ->assertOk()
        ->assertJson(['success' => true]);
});

test('la numeración sigue después de un gafete viejo sin sufijo', function () {
    Storage::fake('public');

    QrCode::factory()->create(['code' => 'MIX-0007']);

    $next = app(QrCodeService::class)->generateBatch(1, 'MIX')->first();

    expect($next->code)->toStartWith('MIX-0008-');
});
