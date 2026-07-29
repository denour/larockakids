<?php

use App\Enums\AttendanceStatus;
use App\Enums\QrCodeStatus;
use App\Events\WhatsAppNotification;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\QrCode;
use App\Models\TutorMessage;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Hola [tutor], [nino] ha llegado.',
        'description' => 'Mensaje de entrada',
        'is_active' => true,
    ]);

    TutorMessage::create([
        'label' => 'exit',
        'name' => 'Salida',
        'message' => 'Hola [tutor], [nino] ha salido.',
        'description' => 'Mensaje de salida',
        'is_active' => true,
    ]);

    TutorMessage::create([
        'label' => 'assistance',
        'name' => 'Asistencia',
        'message' => 'Hola [tutor], tu hijo(a) [nino] necesita asistencia.',
        'description' => 'Mensaje de asistencia',
        'is_active' => true,
    ]);
});

test('check in page loads successfully', function () {
    $this->get(route('scanner.check-in'))
        ->assertStatus(200)
        ->assertSee('ENTRADA');
});

test('check out page loads successfully', function () {
    $this->get(route('scanner.check-out'))
        ->assertStatus(200)
        ->assertSee('SALIDA');
});

test('check in creates attendance for assigned qr', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0001');

    $response = $this->postJson(route('scanner.check-in.process'), ['code' => 'TEST-0001']);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'action' => 'check_in',
            'kid_name' => $kid->full_name,
        ]);

    $this->assertDatabaseHas('attendances', [
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'status' => AttendanceStatus::EN_CLASE->value,
    ]);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('check in sends assistance when already checked in', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0002');

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->postJson(route('scanner.check-in.process'), ['code' => 'TEST-0002'])
        ->assertStatus(200)
        ->assertJson(['success' => true, 'action' => 'assistance']);

    $this->assertDatabaseCount('attendances', 1);
    Event::assertDispatched(WhatsAppNotification::class);
});

test('check in fails for unassigned qr code', function () {
    Event::fake([WhatsAppNotification::class]);

    QrCode::factory()->create([
        'code' => 'TEST-0003',
        'kid_id' => null,
        'status' => QrCodeStatus::Available,
    ]);

    $this->postJson(route('scanner.check-in.process'), ['code' => 'TEST-0003'])
        ->assertStatus(200)
        ->assertJson(['success' => false])
        ->assertJsonFragment(['message' => 'Este código QR no está asignado a ningún niño.']);

    $this->assertDatabaseCount('attendances', 0);
    Event::assertNotDispatched(WhatsAppNotification::class);
});

test('check in fails for invalid qr code', function () {
    Event::fake([WhatsAppNotification::class]);

    $this->postJson(route('scanner.check-in.process'), ['code' => 'INVALID-CODE'])
        ->assertStatus(200)
        ->assertJson(['success' => false])
        ->assertJsonFragment(['message' => 'Código QR no encontrado.']);

    Event::assertNotDispatched(WhatsAppNotification::class);
});

test('check out updates attendance', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0004');

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->postJson(route('scanner.check-out.process'), ['code' => 'TEST-0004'])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'action' => 'check_out',
            'kid_name' => $kid->full_name,
        ]);

    $attendance->refresh();
    expect($attendance->check_out)->not->toBeNull()
        ->and($attendance->status)->toBe(AttendanceStatus::RETIRADO);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('check out fails when not checked in', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0005');

    $this->postJson(route('scanner.check-out.process'), ['code' => 'TEST-0005'])
        ->assertStatus(200)
        ->assertJson(['success' => false])
        ->assertJsonFragment(['message' => 'No hay entrada registrada para '.$kid->full_name.' hoy.']);

    Event::assertNotDispatched(WhatsAppNotification::class);
});

test('check in validates code is required', function () {
    $this->postJson(route('scanner.check-in.process'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('check out validates code is required', function () {
    $this->postJson(route('scanner.check-out.process'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('check in prioritizes parent contact', function () {
    Event::fake([WhatsAppNotification::class]);

    $parentContact = Contact::factory()->create();
    $guardianContact = Contact::factory()->create();
    $kid = Kid::factory()->create(['birth_date' => now()->subYears(3)]);
    $kid->contacts()->sync([]);
    $kid->contacts()->attach($guardianContact->id, ['relationship_type' => 'guardian']);
    $kid->contacts()->attach($parentContact->id, ['relationship_type' => 'parent']);

    createAssignedQr($kid, 'TEST-0006');

    $this->postJson(route('scanner.check-in.process'), ['code' => 'TEST-0006'])
        ->assertStatus(200)
        ->assertJson(['success' => true, 'action' => 'check_in']);

    $this->assertDatabaseHas('attendances', [
        'kid_id' => $kid->id,
        'contact_id' => $parentContact->id,
    ]);
});

test('check in stores client ip', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0007');

    $this->postJson(route('scanner.check-in.process'), [
        'code' => 'TEST-0007',
    ], ['REMOTE_ADDR' => '192.168.1.100'])
        ->assertStatus(200)
        ->assertJson(['success' => true, 'action' => 'check_in']);

    $this->assertDatabaseHas('attendances', [
        'kid_id' => $kid->id,
        'check_in_ip' => '192.168.1.100',
    ]);
});

test('check out fails when ip does not match', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0008');

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'check_in_ip' => '192.168.1.100',
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->postJson(route('scanner.check-out.process'), [
        'code' => 'TEST-0008',
    ], ['REMOTE_ADDR' => '192.168.1.200'])
        ->assertStatus(200)
        ->assertJson(['success' => false])
        ->assertJsonFragment(['message' => 'Error de autenticación: esta acción debe realizarse desde el dispositivo original.']);

    Event::assertNotDispatched(WhatsAppNotification::class);
});

test('check out succeeds when ip matches', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0009');

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'check_in_ip' => '192.168.1.100',
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->postJson(route('scanner.check-out.process'), [
        'code' => 'TEST-0009',
    ], ['REMOTE_ADDR' => '192.168.1.100'])
        ->assertStatus(200)
        ->assertJson(['success' => true, 'action' => 'check_out']);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('assistance message fails when ip does not match', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0010');

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'check_in_ip' => '192.168.1.100',
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->postJson(route('scanner.check-in.process'), [
        'code' => 'TEST-0010',
    ], ['REMOTE_ADDR' => '192.168.1.200'])
        ->assertStatus(200)
        ->assertJson(['success' => false])
        ->assertJsonFragment(['message' => 'Error de autenticación: esta acción debe realizarse desde el dispositivo original.']);

    Event::assertNotDispatched(WhatsAppNotification::class);
});

test('assistance message succeeds when ip matches', function () {
    Event::fake([WhatsAppNotification::class]);

    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'TEST-0011');

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'check_in_ip' => '192.168.1.100',
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->postJson(route('scanner.check-in.process'), [
        'code' => 'TEST-0011',
    ], ['REMOTE_ADDR' => '192.168.1.100'])
        ->assertStatus(200)
        ->assertJson(['success' => true, 'action' => 'assistance']);

    Event::assertDispatched(WhatsAppNotification::class);
});
