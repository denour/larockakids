<?php

use App\Models\Kid;
use App\Models\Contact;
use App\Models\QrCode;
use App\Models\User;

// QrCodePrintController
test('print single qr code returns view', function () {
    $qrCode = QrCode::factory()->create();

    $this->get(route('qr-codes.print', $qrCode))
        ->assertStatus(200)
        ->assertSee($qrCode->code);
});

test('print batch qr codes returns view', function () {
    $qrCodes = QrCode::factory()->count(3)->create();
    $ids = $qrCodes->pluck('id')->join(',');

    $response = $this->get(route('qr-codes.print-batch', ['ids' => $ids]));

    $response->assertStatus(200);
    foreach ($qrCodes as $qrCode) {
        $response->assertSee($qrCode->code);
    }
});

test('print batch with invalid ids handles gracefully', function () {
    $response = $this->get(route('qr-codes.print-batch', ['ids' => '99999']));

    $response->assertStatus(200);
});

// WhatsAppController
test('whatsapp page loads', function () {
    $this->get('/whatsapp')
        ->assertStatus(200);
});

// Auth
test('admin login page loads', function () {
    $this->get('/admin/login')
        ->assertStatus(200);
});

test('authenticated user can access admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(200);
});

test('unauthenticated user is redirected from admin', function () {
    $this->get('/admin')
        ->assertRedirect();
});

test('admin dashboard loads for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(200);
});
