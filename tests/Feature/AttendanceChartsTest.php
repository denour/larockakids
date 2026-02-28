<?php

use App\Filament\Widgets\AttendanceComparisonChart;
use App\Filament\Widgets\AttendanceStats;
use App\Filament\Widgets\QuarterlyAttendanceChart;
use App\Filament\Widgets\WeeklyAttendanceChart;
use App\Filament\Widgets\YearlyAttendanceChart;
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
        'is_active' => true,
    ]);

    $this->kid->contacts()->attach($this->contact->id, ['relationship_type' => 'parent']);
});

test('attendance stats widget renders', function () {
    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today(),
    ]);

    Livewire::test(AttendanceStats::class)
        ->assertSee('Total Asistencias')
        ->assertSee('Presentes')
        ->assertSee('Salidas');
});

test('weekly attendance chart renders', function () {
    $sunday = Carbon::now()->startOfMonth();
    while (! $sunday->isSunday()) {
        $sunday->addDay();
    }

    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => $sunday,
    ]);

    Livewire::test(WeeklyAttendanceChart::class)
        ->assertSee('Asistencia del Mes Actual');
});

test('quarterly attendance chart renders', function () {
    Livewire::test(QuarterlyAttendanceChart::class)
        ->assertSee('Asistencia Últimos 3 Meses');
});

test('yearly attendance chart renders', function () {
    Livewire::test(YearlyAttendanceChart::class)
        ->assertSee('Asistencia Mensual (Último Año)');
});

test('attendance comparison chart renders', function () {
    Livewire::test(AttendanceComparisonChart::class)
        ->assertSee('Comparativa: Este Mes vs Mes Anterior');
});

test('attendance stats counts correctly', function () {
    Attendance::create([
        'kid_id' => $this->kid->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today(),
        'check_out' => null,
    ]);

    $kid2 = Kid::create([
        'first_name' => 'Carlos',
        'last_name' => 'López',
        'birth_date' => now()->subYears(4),
        'gender' => 'male',
        'is_active' => true,
    ]);
    $kid2->contacts()->attach($this->contact->id, ['relationship_type' => 'Tío']);

    Attendance::create([
        'kid_id' => $kid2->id,
        'contact_id' => $this->contact->id,
        'check_in' => Carbon::today(),
        'check_out' => Carbon::now(),
    ]);

    Livewire::test(AttendanceStats::class)
        ->assertSee('2');
});
