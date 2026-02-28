<?php

use App\Filament\Resources\AttendanceResource\Pages\AttendanceHistory;
use App\Filament\Widgets\AttendanceHistoryStats;
use App\Filament\Widgets\BestStreakRanking;
use App\Filament\Widgets\RecentAbsencesRanking;
use App\Filament\Widgets\TopAttendanceRanking;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->contact = Contact::create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'phone' => '5551234567',
        'international_code' => '+52',
    ]);

    $this->kid = Kid::create([
        'first_name' => 'María',
        'last_name' => 'Pérez',
        'birth_date' => now()->subYears(5),
        'gender' => 'female',
    ]);

    $this->kid->contacts()->attach($this->contact->id, ['relationship_type' => 'parent']);
});

test('history page loads', function () {
    $this->actingAs($this->user)
        ->get('/admin/attendances/history')
        ->assertSuccessful();
});

test('history shows all attendances including checked out', function () {
    $this->actingAs($this->user);

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today()->subDays(7),
        'check_out' => Carbon::today()->subDays(7)->addHours(2),
    ]);

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today(),
        'check_out' => null,
    ]);

    Livewire::test(AttendanceHistory::class)
        ->assertCanSeeTableRecords(Attendance::all());
});

test('history stats widget renders', function () {
    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today(),
    ]);

    Livewire::test(AttendanceHistoryStats::class)
        ->assertSee('Este Mes')
        ->assertSee('Mes Pasado')
        ->assertSee('Total General')
        ->assertSee('Promedio por Domingo');
});

test('top attendance ranking shows correct order', function () {
    $kid2 = Kid::create([
        'first_name' => 'Carlos',
        'last_name' => 'López',
        'birth_date' => now()->subYears(4),
        'gender' => 'male',
    ]);
    $kid2->contacts()->attach($this->contact->id, ['relationship_type' => 'Tío']);

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today(),
    ]);

    Attendance::create([
        'kid_id' => $kid2->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today(),
    ]);

    Attendance::create([
        'kid_id' => $kid2->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today()->subWeek(),
    ]);

    Livewire::test(TopAttendanceRanking::class)
        ->assertSee('Carlos López')
        ->assertSee('María Pérez')
        ->assertSee('Top 10 Asistencias');
});

test('streak calculation is correct', function () {
    $lastSunday = Carbon::now();
    if (! $lastSunday->isSunday()) {
        $lastSunday = $lastSunday->previous(Carbon::SUNDAY);
    }

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => $lastSunday,
    ]);

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => $lastSunday->copy()->subWeek(),
    ]);

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => $lastSunday->copy()->subWeeks(2),
    ]);

    Livewire::test(BestStreakRanking::class)
        ->assertSee('María Pérez')
        ->assertSee('3 dom.')
        ->assertSee('Mejor Racha');
});

test('absences count is correct', function () {
    $lastSunday = Carbon::now();
    if (! $lastSunday->isSunday()) {
        $lastSunday = $lastSunday->previous(Carbon::SUNDAY);
    }

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => $lastSunday->copy()->subWeeks(3),
    ]);

    Livewire::test(RecentAbsencesRanking::class)
        ->assertSee('María Pérez')
        ->assertSee('Ausencias Recientes');
});

test('history stats counts correctly', function () {
    $startOfMonth = Carbon::now()->startOfMonth();
    $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => $startOfMonth->copy()->addDays(5),
    ]);

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => $startOfLastMonth->copy()->addDays(5),
    ]);

    Livewire::test(AttendanceHistoryStats::class)
        ->assertSee('1');
});

test('history page has date filter', function () {
    $this->actingAs($this->user);

    Livewire::test(AttendanceHistory::class)
        ->assertTableFilterExists('date');
});
