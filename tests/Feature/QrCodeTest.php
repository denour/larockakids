<?php

use App\Enums\QrCodeStatus;
use App\Models\Kid;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('it can create a qr code', function () {
    $qrCode = QrCode::factory()->create(['code' => 'LRK-0001']);

    $this->assertDatabaseHas('qr_codes', [
        'id' => $qrCode->id,
        'code' => 'LRK-0001',
        'status' => QrCodeStatus::Available->value,
    ]);
});

test('it can generate a batch of qr codes', function () {
    $service = app(QrCodeService::class);
    $qrCodes = $service->generateBatch(5, 'TEST');

    expect($qrCodes)->toHaveCount(5)
        ->and($qrCodes->first()->code)->toMatch('/^TEST-0001-[A-Z2-9]{6}$/')
        ->and($qrCodes->last()->code)->toMatch('/^TEST-0005-[A-Z2-9]{6}$/');

    foreach ($qrCodes as $qrCode) {
        $this->assertDatabaseHas('qr_codes', [
            'id' => $qrCode->id,
            'status' => QrCodeStatus::Available->value,
        ]);
        expect($qrCode->qr_image_path)->not->toBeNull();
    }
});

test('it continues sequence when generating more codes', function () {
    $service = app(QrCodeService::class);

    $service->generateBatch(3, 'SEQ');
    $secondBatch = $service->generateBatch(2, 'SEQ');

    expect($secondBatch->first()->code)->toStartWith('SEQ-0004-')
        ->and($secondBatch->last()->code)->toStartWith('SEQ-0005-');
});

test('it can assign qr code to kid', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->create();

    expect($qrCode->isAvailable())->toBeTrue();

    $qrCode->assignToKid($kid);
    $qrCode->refresh();

    expect($qrCode->isAssigned())->toBeTrue()
        ->and($qrCode->kid_id)->toBe($kid->id)
        ->and($qrCode->assigned_at)->not->toBeNull();
});

test('it can mark qr code as lost', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->assigned($kid)->create();

    expect($qrCode->isAssigned())->toBeTrue();

    $qrCode->markAsLost();
    $qrCode->refresh();

    expect($qrCode->isLost())->toBeTrue()
        ->and($qrCode->kid_id)->toBeNull()
        ->and($qrCode->assigned_at)->toBeNull();
});

test('it can unassign qr code', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->assigned($kid)->create();

    $qrCode->unassign();
    $qrCode->refresh();

    expect($qrCode->isAvailable())->toBeTrue()
        ->and($qrCode->kid_id)->toBeNull()
        ->and($qrCode->assigned_at)->toBeNull();
});

test('kid can have assigned qr code', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->assigned($kid)->create();

    expect($kid->qrCode)->not->toBeNull()
        ->and($kid->qrCode->id)->toBe($qrCode->id);
});

test('kid loses qr code when marked as lost', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->assigned($kid)->create();

    expect($kid->fresh()->qrCode)->not->toBeNull();

    $qrCode->markAsLost();

    expect($kid->fresh()->qrCode)->toBeNull();
});

test('kid can be assigned new qr after losing old one', function () {
    $kid = Kid::factory()->create();
    $oldQrCode = QrCode::factory()->assigned($kid)->create();
    $newQrCode = QrCode::factory()->create();

    $oldQrCode->markAsLost();
    expect($kid->fresh()->qrCode)->toBeNull();

    $newQrCode->assignToKid($kid);
    expect($kid->fresh()->qrCode)->not->toBeNull()
        ->and($kid->fresh()->qrCode->id)->toBe($newQrCode->id);
});

test('qr code status enum has correct labels', function () {
    expect(QrCodeStatus::Available->getLabel())->toBe('Disponible')
        ->and(QrCodeStatus::Assigned->getLabel())->toBe('Asignado')
        ->and(QrCodeStatus::Lost->getLabel())->toBe('Perdido');
});

test('qr code status enum has correct colors', function () {
    expect(QrCodeStatus::Available->getColor())->toBe('success')
        ->and(QrCodeStatus::Assigned->getColor())->toBe('info')
        ->and(QrCodeStatus::Lost->getColor())->toBe('danger');
});

test('it can print single qr code', function () {
    $qrCode = QrCode::factory()->create();

    $this->actingAs(\App\Models\User::factory()->create())
        ->get(route('qr-codes.print', $qrCode))
        ->assertStatus(200)
        ->assertSee($qrCode->code);
});

test('it can print batch of qr codes', function () {
    $qrCodes = QrCode::factory()->count(3)->create();
    $ids = $qrCodes->pluck('id')->join(',');

    $response = $this->actingAs(\App\Models\User::factory()->create())
        ->get(route('qr-codes.print-batch', ['ids' => $ids]));

    $response->assertStatus(200);
    foreach ($qrCodes as $qrCode) {
        $response->assertSee($qrCode->code);
    }
});

test('qr code requires unique code', function () {
    QrCode::factory()->create(['code' => 'LRK-0001']);
    QrCode::factory()->create(['code' => 'LRK-0001']);
})->throws(\Illuminate\Database\QueryException::class);

test('qr code belongs to kid', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->assigned($kid)->create();

    expect($qrCode->kid)->toBeInstanceOf(Kid::class)
        ->and($qrCode->kid->id)->toBe($kid->id);
});
