<?php

use App\Events\WhatsAppNotification;
use App\Filament\Resources\AttendanceResource\Pages\CreateAttendance;
use App\Mcp\Servers\AttendanceServer;
use App\Mcp\Tools\createAttendance as CreateAttendanceTool;
use App\Models\Kid;
use App\Models\TutorMessage;
use App\Models\User;
use App\Services\AttendanceScannerService;
use App\Services\BirthdayGreetingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    Event::fake([WhatsAppNotification::class]);

    TutorMessage::create(['label' => 'entry', 'name' => 'Entrada', 'message' => 'Entrada de [nino].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'welcome', 'name' => 'Bienvenida', 'message' => 'Bienvenido [nino].', 'description' => 'Test', 'is_active' => true]);
});

function birthdayMessagesSent(): int
{
    return Event::dispatched(WhatsAppNotification::class)
        ->filter(fn (array $args) => str_contains($args[0]->message, 'feliz cumpleaños'))
        ->count();
}

// =============================================================
// La plantilla viene de una migración (el seeder no corre en producción)
// =============================================================

test('the birthday template exists and is active after migrating', function () {
    $template = TutorMessage::findByLabel('birthday');

    expect($template)->not->toBeNull()
        ->and($template->replaceTags(['[tutor]' => 'Ana', '[nino]' => 'Luis']))
        ->toContain('Ana')
        ->toContain('Luis');
});

// =============================================================
// ¿Cayó el cumpleaños en la última semana?
// =============================================================

test('recentBirthday detects birthdays in the last seven days', function (string $today, string $birthDate, ?string $expected) {
    $kid = Kid::factory()->create(['birth_date' => $birthDate]);

    $birthday = app(BirthdayGreetingService::class)->recentBirthday($kid, Carbon::parse($today));

    expect($birthday?->toDateString())->toBe($expected);
})->with([
    'hoy' => ['2025-10-12', '2021-10-12', '2025-10-12'],
    'ayer' => ['2025-10-12', '2021-10-11', '2025-10-11'],
    'hace 7 días' => ['2025-10-12', '2021-10-05', '2025-10-05'],
    'hace 8 días' => ['2025-10-12', '2021-10-04', null],
    'mañana' => ['2025-10-12', '2021-10-13', null],
    'cruza el año' => ['2026-01-03', '2021-12-30', '2025-12-30'],
    'nació hoy' => ['2025-10-12', '2025-10-12', null],
    '29 de febrero en año no bisiesto' => ['2025-03-02', '2020-02-29', '2025-03-01'],
]);

// =============================================================
// Se felicita una sola vez por cumpleaños
// =============================================================

test('greetIfDue sends the greeting and remembers it', function () {
    $this->travelTo('2025-10-12 11:00:00');
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => '2021-10-09']);

    $sent = app(BirthdayGreetingService::class)->greetIfDue($kid, $contact);

    expect($sent)->toBeTrue()
        ->and(birthdayMessagesSent())->toBe(1)
        ->and($kid->refresh()->birthday_greeted_on->toDateString())->toBe('2025-10-09');
});

test('the same birthday is not greeted twice, not even the next sunday', function () {
    $this->travelTo('2025-10-12 11:00:00');
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => '2021-10-12']);
    $service = app(BirthdayGreetingService::class);

    expect($service->greetIfDue($kid, $contact))->toBeTrue();

    $this->travelTo('2025-10-12 13:30:00');
    expect($service->greetIfDue($kid->refresh(), $contact))->toBeFalse();

    $this->travelTo('2025-10-19 11:00:00');
    expect($service->greetIfDue($kid->refresh(), $contact))->toBeFalse()
        ->and(birthdayMessagesSent())->toBe(1);
});

test('the next birthday is greeted again a year later', function () {
    $this->travelTo('2025-10-12 11:00:00');
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => '2021-10-12']);
    $service = app(BirthdayGreetingService::class);

    $service->greetIfDue($kid, $contact);

    $this->travelTo('2026-10-18 11:00:00');
    $service->greetIfDue($kid->refresh(), $contact);

    expect(birthdayMessagesSent())->toBe(2)
        ->and($kid->refresh()->birthday_greeted_on->toDateString())->toBe('2026-10-12');
});

test('nothing is sent when the birthday is not recent', function () {
    $this->travelTo('2025-10-12 11:00:00');
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => '2021-06-01']);

    expect(app(BirthdayGreetingService::class)->greetIfDue($kid, $contact))->toBeFalse()
        ->and(birthdayMessagesSent())->toBe(0)
        ->and($kid->refresh()->birthday_greeted_on)->toBeNull();
});

test('an inactive template sends nothing and does not mark the birthday as greeted', function () {
    $this->travelTo('2025-10-12 11:00:00');
    TutorMessage::where('label', 'birthday')->update(['is_active' => false]);
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => '2021-10-10']);

    expect(app(BirthdayGreetingService::class)->greetIfDue($kid, $contact))->toBeFalse()
        ->and(birthdayMessagesSent())->toBe(0)
        ->and($kid->refresh()->birthday_greeted_on)->toBeNull();
});

// =============================================================
// Las tres formas de registrar una entrada felicitan
// =============================================================

test('a kiosk check-in greets a recent birthday and says so on screen', function () {
    $this->travelTo('2025-10-12 11:00:00');
    ['kid' => $kid] = createKidWithContact(['birth_date' => '2022-10-08']);
    createAssignedQr($kid, 'BDAY-0001');

    $result = app(AttendanceScannerService::class)->processCheckIn('BDAY-0001');

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('cumpleaños')
        ->and(birthdayMessagesSent())->toBe(1);
});

test('a kiosk check-in without a recent birthday does not mention it', function () {
    $this->travelTo('2025-10-12 11:00:00');
    ['kid' => $kid] = createKidWithContact(['birth_date' => '2022-05-08']);
    createAssignedQr($kid, 'BDAY-0002');

    $result = app(AttendanceScannerService::class)->processCheckIn('BDAY-0002');

    expect($result['message'])->not->toContain('cumpleaños')
        ->and(birthdayMessagesSent())->toBe(0);
});

test('checking in to both services the same day greets only once', function () {
    $this->travelTo('2025-10-12 11:00:00');
    ['kid' => $kid] = createKidWithContact(['birth_date' => '2022-10-12']);
    createAssignedQr($kid, 'BDAY-0003');
    $scanner = app(AttendanceScannerService::class);

    $scanner->processCheckIn('BDAY-0003');
    $scanner->processCheckOut('BDAY-0003');

    $this->travelTo('2025-10-12 13:30:00');
    $scanner->processCheckIn('BDAY-0003');

    expect($kid->attendances()->count())->toBe(2)
        ->and(birthdayMessagesSent())->toBe(1);
});

test('creating an attendance from the panel greets a recent birthday', function () {
    $this->travelTo('2025-10-12 11:00:00');
    $this->actingAs(User::factory()->create());
    ['kid' => $kid, 'contact' => $contact] = createKidWithContact(['birth_date' => '2022-10-07']);

    Livewire::test(CreateAttendance::class)
        ->fillForm(['kid_id' => $kid->id, 'contact_id' => $contact->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(birthdayMessagesSent())->toBe(1);
});

test('the mcp createAttendance tool greets a recent birthday', function () {
    $this->travelTo('2025-10-12 11:00:00');

    AttendanceServer::tool(CreateAttendanceTool::class, [
        'kid' => ['first_name' => 'Luis', 'last_name' => 'Pérez', 'birth_date' => '2022-10-10', 'gender' => 'male'],
        'contact' => ['first_name' => 'Ana', 'last_name' => 'Pérez', 'phone' => '6641234567'],
    ])->assertOk();

    expect(birthdayMessagesSent())->toBe(1);
});
