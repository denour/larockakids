<?php

use App\Enums\AttendanceStatus;
use App\Enums\QrCodeStatus;
use App\Events\WhatsAppNotification;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\QrCode;
use App\Models\TutorMessage;
use App\Services\AttendanceScannerService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake([WhatsAppNotification::class]);

    TutorMessage::create(['label' => 'entry', 'name' => 'Entrada', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'exit', 'name' => 'Salida', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'assistance', 'name' => 'Asistencia', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);

    $this->service = app(AttendanceScannerService::class);
});

test('process check in creates attendance', function () {
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact();
    createAssignedQr($kid, 'SVC-0001');

    $result = $this->service->processCheckIn('SVC-0001');

    expect($result['success'])->toBeTrue()
        ->and($result['action'])->toBe('check_in');

    $this->assertDatabaseHas('attendances', ['kid_id' => $kid->id]);
});

test('process check in fails for invalid code', function () {
    $result = $this->service->processCheckIn('INVALID');

    expect($result['success'])->toBeFalse();
});

test('process check in fails for unassigned code', function () {
    QrCode::factory()->create(['code' => 'SVC-0002', 'status' => QrCodeStatus::Available]);

    $result = $this->service->processCheckIn('SVC-0002');

    expect($result['success'])->toBeFalse();
});

test('process check out updates attendance', function () {
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact();
    createAssignedQr($kid, 'SVC-0003');

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $result = $this->service->processCheckOut('SVC-0003');

    expect($result['success'])->toBeTrue()
        ->and($result['action'])->toBe('check_out');
});

test('process check out fails when no active attendance', function () {
    ['kid' => $kid] = createKidWithContact();
    createAssignedQr($kid, 'SVC-0004');

    $result = $this->service->processCheckOut('SVC-0004');

    expect($result['success'])->toBeFalse();
});

test('get primary contact prioritizes parent', function () {
    $parentContact = Contact::factory()->create();
    $guardianContact = Contact::factory()->create();
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $kid->contacts()->attach($guardianContact->id, ['relationship_type' => 'guardian']);
    $kid->contacts()->attach($parentContact->id, ['relationship_type' => 'parent']);

    $primary = $this->service->getPrimaryContact($kid);

    expect($primary->id)->toBe($parentContact->id);
});

test('get active attendance today returns todays attendance', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $found = $this->service->getActiveAttendanceToday($kid);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($attendance->id);
});

test('get active attendance today returns null when no attendance', function () {
    $kid = Kid::factory()->create();

    expect($this->service->getActiveAttendanceToday($kid))->toBeNull();
});

test('get active attendance today returns null for checked out', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'check_out' => now()->addHours(2),
        'status' => AttendanceStatus::RETIRADO,
    ]);

    expect($this->service->getActiveAttendanceToday($kid))->toBeNull();
});
