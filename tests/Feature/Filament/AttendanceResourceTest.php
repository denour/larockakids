<?php

use App\Enums\AttendanceStatus;
use App\Events\WhatsAppNotification;
use App\Filament\Resources\AttendanceResource\Pages\CreateAttendance;
use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\TutorMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    TutorMessage::create(['label' => 'entry', 'name' => 'Entrada', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'exit', 'name' => 'Salida', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'assistance', 'name' => 'Asistencia', 'message' => 'Hola [tutor].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'bathroom', 'name' => 'Baño', 'message' => '[nino] baño.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'diaper', 'name' => 'Pañal', 'message' => '[nino] pañal.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'sick', 'name' => 'Enfermo', 'message' => '[nino] enfermo.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'recovered', 'name' => 'Recuperado', 'message' => '[nino] ok.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'unconsolable', 'name' => 'Inconsolable', 'message' => '[nino] llora.', 'description' => 'Test', 'is_active' => true]);
});

test('attendance list page renders', function () {
    $this->get('/admin/attendances')
        ->assertSuccessful();
});

test('attendance list shows active attendances', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    Livewire::test(ListAttendances::class)
        ->assertCanSeeTableRecords(collect([$attendance]));
});

test('attendance list does not show checked out records', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'check_out' => now()->addHours(2),
        'status' => AttendanceStatus::RETIRADO,
    ]);

    Livewire::test(ListAttendances::class)
        ->assertCanNotSeeTableRecords(collect([$attendance]));
});

test('attendance create page renders', function () {
    $this->get('/admin/attendances/create')
        ->assertSuccessful();
});

test('attendance can be created via form', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    Event::fake([WhatsAppNotification::class]);

    Livewire::test(CreateAttendance::class)
        ->fillForm([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('attendances', [
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
    ]);
});

test('attendance list can search by kid name', function () {
    $kid = Kid::factory()->create(['first_name' => 'SearchableKid']);
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    Livewire::test(ListAttendances::class)
        ->searchTable('SearchableKid')
        ->assertCanSeeTableRecords(collect([$attendance]));
});

test('attendance has exit action', function () {
    Event::fake([WhatsAppNotification::class]);

    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    Livewire::test(ListAttendances::class)
        ->callTableAction('exit', $attendance);

    $attendance->refresh();
    expect($attendance->check_out)->not->toBeNull()
        ->and($attendance->status)->toBe(AttendanceStatus::RETIRADO);
});

/**
 * Crea una asistencia SIN salida (la lista solo muestra niños dentro) en la fecha dada.
 */
function attendanceInsideAt(string $checkIn): Attendance
{
    $kid = Kid::factory()->create();

    return Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $kid->contacts->first()->id,
        'check_in' => $checkIn,
        'status' => AttendanceStatus::EN_CLASE,
    ]);
}

test('the date filters return the right attendances', function (string $filter, array $expectedDates) {
    // Miércoles 15 de octubre de 2025, mediodía.
    $this->travelTo('2025-10-15 12:00:00');

    $dates = [
        '2025-10-15 11:00:00', // hoy
        '2025-10-14 11:00:00', // ayer (martes, misma semana)
        '2025-10-13 00:30:00', // lunes, inicio de esta semana
        '2025-10-12 11:00:00', // domingo, semana pasada
        '2025-10-06 11:00:00', // lunes de la semana pasada
        '2025-10-05 11:00:00', // hace dos semanas
        '2025-09-30 11:00:00', // mes pasado
        '2024-10-15 11:00:00', // mismo mes, año pasado
        '2024-09-15 11:00:00', // mismo "mes pasado", año pasado
    ];

    $attendances = collect($dates)->mapWithKeys(fn (string $date) => [$date => attendanceInsideAt($date)]);

    $expected = $attendances->only($expectedDates);

    Livewire::test(ListAttendances::class)
        ->filterTable('date', $filter)
        ->assertCanSeeTableRecords($expected)
        ->assertCanNotSeeTableRecords($attendances->except($expectedDates))
        ->assertCountTableRecords($expected->count());
})->with([
    'hoy' => ['today', ['2025-10-15 11:00:00']],
    'ayer' => ['yesterday', ['2025-10-14 11:00:00']],
    'esta semana' => ['this_week', ['2025-10-15 11:00:00', '2025-10-14 11:00:00', '2025-10-13 00:30:00']],
    'semana pasada' => ['last_week', ['2025-10-12 11:00:00', '2025-10-06 11:00:00']],
    'este mes' => ['this_month', ['2025-10-15 11:00:00', '2025-10-14 11:00:00', '2025-10-13 00:30:00', '2025-10-12 11:00:00', '2025-10-06 11:00:00', '2025-10-05 11:00:00']],
    'mes pasado' => ['last_month', ['2025-09-30 11:00:00']],
]);

test('the last month filter does not skip february on the 31st', function () {
    $this->travelTo('2025-03-31 12:00:00');

    $february = attendanceInsideAt('2025-02-10 11:00:00');
    $march = attendanceInsideAt('2025-03-03 11:00:00');

    Livewire::test(ListAttendances::class)
        ->filterTable('date', 'last_month')
        ->assertCanSeeTableRecords([$february])
        ->assertCanNotSeeTableRecords([$march]);
});

test('attendance has navigation badge', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    expect(\App\Filament\Resources\AttendanceResource::getNavigationBadge())->toBe('1');
});

/**
 * El botón "Notificar" abría un modal para elegir el tipo de mensaje. En el
 * salón eso sobra: cuando se aprieta es porque el niño necesita a su tutor.
 */
test('the notify action sends the assistance message straight away, with no modal', function () {
    Event::fake([WhatsAppNotification::class]);

    $contact = Contact::factory()->create(['phone' => '6641234567', 'international_code' => '52']);
    $kid = Kid::factory()->create();
    $attendance = Attendance::factory()->create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_out' => null,
    ]);

    Livewire::test(ListAttendances::class)
        ->callTableAction('whatsapp', $attendance)
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    Event::assertDispatched(WhatsAppNotification::class);
});

test('the notify action warns instead of lying when the assistance message is off', function () {
    Event::fake([WhatsAppNotification::class]);

    TutorMessage::where('label', 'assistance')->update(['is_active' => false]);

    $contact = Contact::factory()->create(['phone' => '6641234567', 'international_code' => '52']);
    $kid = Kid::factory()->create();
    $attendance = Attendance::factory()->create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_out' => null,
    ]);

    Livewire::test(ListAttendances::class)
        ->callTableAction('whatsapp', $attendance)
        ->assertNotified();

    Event::assertNotDispatched(WhatsAppNotification::class);
});
