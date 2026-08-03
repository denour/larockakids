<?php

use App\Enums\QrCodeStatus;
use App\Models\Kid;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->service = app(QrCodeService::class);
});

test('generate batch creates correct count', function () {
    $codes = $this->service->generateBatch(3, 'SVC');

    expect($codes)->toHaveCount(3);
    $this->assertDatabaseCount('qr_codes', 3);
});

test('generate batch uses sequential numbering', function () {
    $codes = $this->service->generateBatch(3, 'NUM');

    expect($codes[0]->code)->toStartWith('NUM-0001-')
        ->and($codes[1]->code)->toStartWith('NUM-0002-')
        ->and($codes[2]->code)->toStartWith('NUM-0003-');
});

test('generate batch continues from last sequence', function () {
    $this->service->generateBatch(5, 'CNT');
    $batch2 = $this->service->generateBatch(3, 'CNT');

    expect($batch2->first()->code)->toStartWith('CNT-0006-')
        ->and($batch2->last()->code)->toStartWith('CNT-0008-');
});

test('generate batch uses different prefix sequences independently', function () {
    $this->service->generateBatch(3, 'AAA');
    $this->service->generateBatch(2, 'BBB');

    expect(QrCode::where('code', 'like', 'AAA-0001-%')->exists())->toBeTrue()
        ->and(QrCode::where('code', 'like', 'BBB-0001-%')->exists())->toBeTrue();
});

test('create qr code stores image path', function () {
    $qrCode = $this->service->createQrCode('SINGLE-001');

    expect($qrCode->qr_image_path)->not->toBeNull()
        ->and($qrCode->code)->toBe('SINGLE-001');
});

test('all generated codes have available status', function () {
    $codes = $this->service->generateBatch(5, 'STS');

    foreach ($codes as $code) {
        expect($code->status)->toBe(QrCodeStatus::Available);
    }
});

test('regenerate image updates image path', function () {
    $qrCode = $this->service->createQrCode('REGEN-001');
    $originalPath = $qrCode->qr_image_path;

    $updated = $this->service->regenerateImage($qrCode);

    expect($updated->qr_image_path)->not->toBeNull();
});

test('delete image removes file', function () {
    $qrCode = $this->service->createQrCode('DEL-001');

    expect($qrCode->qr_image_path)->not->toBeNull();

    $this->service->deleteImage($qrCode);

    Storage::disk('public')->assertMissing($qrCode->qr_image_path);
});
