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
    expect($attendance->check_out)->not->toBeNull();
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
