<?php

use App\Filament\Resources\AttendanceResource\Pages\AttendanceHistory;
use App\Models\Attendance;
use App\Models\Kid;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('attendance history page renders', function () {
    $this->get('/admin/attendances/history')
        ->assertSuccessful();
});

test('attendance history shows all attendances', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $active = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => Carbon::today(),
        'check_out' => null,
    ]);

    $checkedOut = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => Carbon::today()->subDays(7),
        'check_out' => Carbon::today()->subDays(7)->addHours(2),
    ]);

    Livewire::test(AttendanceHistory::class)
        ->assertCanSeeTableRecords(collect([$active, $checkedOut]));
});

test('attendance history has date filter', function () {
    Livewire::test(AttendanceHistory::class)
        ->assertTableFilterExists('date');
});

test('attendance history is read only', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => Carbon::today(),
    ]);

    Livewire::test(AttendanceHistory::class)
        ->assertTableActionDoesNotExist('edit');
});
