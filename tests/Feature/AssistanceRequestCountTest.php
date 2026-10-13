<?php

use App\Enums\AttendanceStatus;
use App\Enums\ServiceTime;
use App\Events\WhatsAppNotification;
use App\Filament\Resources\AttendanceResource\Pages\AttendanceHistory;
use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Mcp\Servers\AttendanceServer;
use App\Mcp\Tools\sendAssistance;
use App\Models\Attendance;
use App\Models\TutorMessage;
use App\Models\User;
use App\Services\AttendanceScannerService;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    Event::fake([WhatsAppNotification::class]);

    TutorMessage::create(['label' => 'entry', 'name' => 'Entrada', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'exit', 'name' => 'Salida', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'assistance', 'name' => 'Asistencia', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);

    // Domingo a las 11: primera reunión. Sin cumpleaños cerca, para no mezclar avisos.
    $this->travelTo('2025-10-12 11:00:00');
});

function insideAttendance(): Attendance
{
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => '2022-03-01']);

    return Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
        'service' => ServiceTime::First,
    ]);
}

test('a new attendance starts with zero assistance requests', function () {
    expect(insideAttendance()->refresh()->assistance_requests)->toBe(0);
});

// =============================================================
// Botón "Notificar" del panel
// =============================================================

test('each press of the notify button is counted on that attendance', function () {
    $this->actingAs(User::factory()->create());
    $attendance = insideAttendance();

    Livewire::test(ListAttendances::class)
        ->callTableAction('whatsapp', $attendance)
        ->callTableAction('whatsapp', $attendance)
        ->callTableAction('whatsapp', $attendance);

    expect($attendance->refresh()->assistance_requests)->toBe(3);
});

test('the notify button does not count when the assistance message is off', function () {
    $this->actingAs(User::factory()->create());
    TutorMessage::where('label', 'assistance')->update(['is_active' => false]);
    $attendance = insideAttendance();

    Livewire::test(ListAttendances::class)
        ->callTableAction('whatsapp', $attendance);

    expect($attendance->refresh()->assistance_requests)->toBe(0);
});

test('the count shows in the attendance list and in the history', function () {
    $this->actingAs(User::factory()->create());
    $attendance = insideAttendance();
    $attendance->recordAssistanceRequest();
    $attendance->recordAssistanceRequest();

    Livewire::test(ListAttendances::class)
        ->assertTableColumnStateSet('assistance_requests', 2, $attendance);

    Livewire::test(AttendanceHistory::class)
        ->assertTableColumnStateSet('assistance_requests', 2, $attendance);
});

// =============================================================
// Kiosco
// =============================================================

test('the kiosk assistance mode counts on the kid active attendance', function () {
    $attendance = insideAttendance();
    createAssignedQr($attendance->kid, 'ASIS-0001');

    app(AttendanceScannerService::class)->processAssistance('ASIS-0001');
    app(AttendanceScannerService::class)->processAssistance('ASIS-0001');

    expect($attendance->refresh()->assistance_requests)->toBe(2);
});

test('the kiosk assistance mode still notifies a kid without attendance today', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => '2022-03-01']);
    createAssignedQr($kid, 'ASIS-0002');

    $result = app(AttendanceScannerService::class)->processAssistance('ASIS-0002');

    expect($result['success'])->toBeTrue()
        ->and(Attendance::count())->toBe(0);
});

test('scanning the entry badge again during the service counts as an assistance request', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => '2022-03-01']);
    createAssignedQr($kid, 'ASIS-0003');
    $scanner = app(AttendanceScannerService::class);

    $scanner->processCheckIn('ASIS-0003');
    $result = $scanner->processCheckIn('ASIS-0003');

    expect($result['action'])->toBe('assistance')
        ->and($kid->attendances()->sole()->assistance_requests)->toBe(1);
});

test('each service keeps its own count', function () {
    ['kid' => $kid] = createKidWithContact(['birth_date' => '2022-03-01']);
    createAssignedQr($kid, 'ASIS-0004');
    $scanner = app(AttendanceScannerService::class);

    $scanner->processCheckIn('ASIS-0004');
    $scanner->processAssistance('ASIS-0004');
    $scanner->processCheckOut('ASIS-0004');

    $this->travelTo('2025-10-12 13:30:00');
    $scanner->processCheckIn('ASIS-0004');
    $scanner->processAssistance('ASIS-0004');
    $scanner->processAssistance('ASIS-0004');

    $counts = $kid->attendances()->get()->mapWithKeys(
        fn (Attendance $attendance) => [$attendance->service->value => $attendance->assistance_requests]
    );

    expect($counts->all())->toBe(['first' => 1, 'second' => 2]);
});

// =============================================================
// MCP
// =============================================================

test('the mcp sendAssistance tool counts on the kid active attendance', function () {
    $attendance = insideAttendance();

    AttendanceServer::tool(sendAssistance::class, ['kid_name' => $attendance->kid->full_name])
        ->assertOk();

    expect($attendance->refresh()->assistance_requests)->toBe(1);
});
