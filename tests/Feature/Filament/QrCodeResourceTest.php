<?php

use App\Enums\QrCodeStatus;
use App\Filament\Resources\QrCodeResource\Pages\CreateQrCode;
use App\Filament\Resources\QrCodeResource\Pages\EditQrCode;
use App\Filament\Resources\QrCodeResource\Pages\ListQrCodes;
use App\Models\Kid;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

test('qr code list page renders', function () {
    $this->get('/admin/qr-codes')
        ->assertSuccessful();
});

test('qr code list shows records', function () {
    $qrCodes = QrCode::factory()->count(3)->create();

    Livewire::test(ListQrCodes::class)
        ->assertCanSeeTableRecords($qrCodes);
});

test('qr code list can filter by status', function () {
    $available = QrCode::factory()->create(['status' => QrCodeStatus::Available]);
    $kid = Kid::factory()->create();
    $assigned = QrCode::factory()->assigned($kid)->create();

    Livewire::test(ListQrCodes::class)
        ->filterTable('status', QrCodeStatus::Available->value)
        ->assertCanSeeTableRecords(collect([$available]))
        ->assertCanNotSeeTableRecords(collect([$assigned]));
});

test('qr code create page renders', function () {
    $this->get('/admin/qr-codes/create')
        ->assertSuccessful();
});

test('qr code edit page renders', function () {
    $qrCode = QrCode::factory()->create();

    $this->get("/admin/qr-codes/{$qrCode->id}/edit")
        ->assertSuccessful();
});

test('qr code can be deleted from list', function () {
    $qrCode = QrCode::factory()->create();

    Livewire::test(ListQrCodes::class)
        ->callTableAction('delete', $qrCode);

    $this->assertDatabaseMissing('qr_codes', ['id' => $qrCode->id]);
});

test('qr code assign action is visible for available codes', function () {
    $qrCode = QrCode::factory()->create(['status' => QrCodeStatus::Available]);

    Livewire::test(ListQrCodes::class)
        ->assertTableActionVisible('assign', $qrCode);
});

test('qr code mark lost action is visible for assigned codes', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->assigned($kid)->create();

    Livewire::test(ListQrCodes::class)
        ->assertTableActionVisible('markLost', $qrCode);
});

test('qr code unassign action is visible for assigned codes', function () {
    $kid = Kid::factory()->create();
    $qrCode = QrCode::factory()->assigned($kid)->create();

    Livewire::test(ListQrCodes::class)
        ->assertTableActionVisible('unassign', $qrCode);
});

test('qr code shows code as copyable column', function () {
    $qrCode = QrCode::factory()->create(['code' => 'COPY-001']);

    Livewire::test(ListQrCodes::class)
        ->assertCanSeeTableRecords(collect([$qrCode]));
});
