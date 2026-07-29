<?php

use App\Enums\AttendanceStatus;
use App\Enums\QrCodeStatus;
use App\Enums\ServiceTime;
use App\Events\WhatsAppNotification;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\QrCode;
use App\Models\TutorMessage;
use App\Services\AttendanceScannerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake([WhatsAppNotification::class]);

    TutorMessage::create(['label' => 'entry', 'name' => 'Entrada', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'exit', 'name' => 'Salida', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'assistance', 'name' => 'Asistencia', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);

    $this->service = app(AttendanceScannerService::class);
});

test('process check in creates attendance', function () {
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => now()->subYears(3)]);
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

test('process check in blocks re-entry while kid is still inside the same service', function () {
    Carbon::setTestNow(Carbon::today()->setTime(11, 0));

    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'SVC-DUP1');

    $this->service->processCheckIn('SVC-DUP1');
    $result = $this->service->processCheckIn('SVC-DUP1');

    expect($result['action'])->toBe('assistance')
        ->and($result['has_active_attendance'])->toBeTrue()
        ->and(Attendance::where('kid_id', $kid->id)->count())->toBe(1);

    Carbon::setTestNow();
});

test('process check in blocks a second attendance in the same service after checkout', function () {
    Carbon::setTestNow(Carbon::today()->setTime(11, 0));

    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'SVC-DUP2');

    $this->service->processCheckIn('SVC-DUP2');
    $this->service->processCheckOut('SVC-DUP2');

    $result = $this->service->processCheckIn('SVC-DUP2');

    expect($result['action'])->toBe('assistance')
        ->and($result['has_active_attendance'])->toBeFalse()
        ->and(Attendance::where('kid_id', $kid->id)->where('service', ServiceTime::First)->count())->toBe(1);

    Carbon::setTestNow();
});

test('process check in allows attendance in a different service after checkout', function () {
    Carbon::setTestNow(Carbon::today()->setTime(11, 0));

    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'SVC-DUP3');

    $this->service->processCheckIn('SVC-DUP3');
    $this->service->processCheckOut('SVC-DUP3');

    Carbon::setTestNow(Carbon::today()->setTime(13, 0));

    $result = $this->service->processCheckIn('SVC-DUP3');

    expect($result['success'])->toBeTrue()
        ->and($result['action'])->toBe('check_in')
        ->and(Attendance::where('kid_id', $kid->id)->count())->toBe(2)
        ->and(Attendance::where('kid_id', $kid->id)->where('service', ServiceTime::Second)->count())->toBe(1);

    Carbon::setTestNow();
});

test('process check in still registers but alerts when kid has reached the giants age', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(4)->subWeek()]);
    createAssignedQr($kid, 'SVC-AGE1');

    $result = $this->service->processCheckIn('SVC-AGE1');

    expect($result['success'])->toBeTrue()
        ->and($result['action'])->toBe('check_in')
        ->and($result['requires_graduation'])->toBeTrue()
        ->and($result['warning'])->toContain('Chicos Gigantes')
        ->and(Attendance::where('kid_id', $kid->id)->count())->toBe(1);
});

test('process check in warns when kid is within four weeks of the giants age', function () {
    Carbon::setTestNow(Carbon::today()->setTime(11, 0));

    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(4)->addWeeks(2)]);
    createAssignedQr($kid, 'SVC-AGE2');

    $result = $this->service->processCheckIn('SVC-AGE2');

    expect($result['success'])->toBeTrue()
        ->and($result['action'])->toBe('check_in')
        ->and($result['warning'])->not->toBeNull()
        ->and($result['warning'])->toContain('Chicos Gigantes')
        ->and($result['requires_graduation'])->toBeFalse()
        ->and(Attendance::where('kid_id', $kid->id)->count())->toBe(1);

    Carbon::setTestNow();
});

test('process check in does not warn when kid is far from the giants age', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(2)]);
    createAssignedQr($kid, 'SVC-AGE3');

    $result = $this->service->processCheckIn('SVC-AGE3');

    expect($result['success'])->toBeTrue()
        ->and($result['action'])->toBe('check_in')
        ->and($result['warning'])->toBeNull()
        ->and($result['requires_graduation'])->toBeFalse();
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
    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
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

test('process check in fails gracefully when the assigned kid was soft deleted', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    $qr = createAssignedQr($kid, 'SVC-DEL1');

    $kid->delete();

    $result = $this->service->processCheckIn('SVC-DEL1');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Este código QR no está asignado a ningún niño.');

    expect($qr->fresh()->kid_id)->toBe($kid->id);
    $this->assertDatabaseMissing('attendances', ['kid_id' => $kid->id]);
});

test('process check out fails gracefully when the assigned kid was soft deleted', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'SVC-DEL2');

    $this->service->processCheckIn('SVC-DEL2');
    $kid->delete();

    $result = $this->service->processCheckOut('SVC-DEL2');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Este código QR no está asignado a ningún niño.');
});

test('process assistance fails gracefully when the assigned kid was soft deleted', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => now()->subYears(3)]);
    createAssignedQr($kid, 'SVC-DEL3');

    $kid->delete();

    $result = $this->service->processAssistance('SVC-DEL3');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Este código QR no está asignado a ningún niño.');
});
