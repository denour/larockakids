<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Kid;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;

/**
 * @param  array<string, mixed>  $attributes
 */
function attendanceFor(Kid $kid, array $attributes): Attendance
{
    return Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $kid->contacts->first()->id,
        ...$attributes,
    ]);
}

test('attendances:clean is scheduled daily at 8 PM Tijuana time', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (ScheduledEvent $event) => str_contains($event->command ?? '', 'attendances:clean'));

    expect($events)->toHaveCount(1);

    $event = $events->first();

    expect($event->expression)->toBe('0 20 * * *')
        ->and($event->timezone)->toBe('America/Tijuana');
});

test('attendances:clean marks checked out attendances as retirado, including old ones', function () {
    $kid = Kid::factory()->create();

    $today = attendanceFor($kid, [
        'check_in' => now()->subHours(2),
        'check_out' => now()->subHour(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $lastMonth = attendanceFor($kid, [
        'check_in' => now()->subMonth(),
        'check_out' => now()->subMonth()->addHour(),
        'status' => AttendanceStatus::NOTIFICADO,
    ]);

    $this->artisan('attendances:clean')
        ->expectsOutput('Se marcaron 2 asistencias como RETIRADO.')
        ->assertSuccessful();

    expect($today->refresh()->status)->toBe(AttendanceStatus::RETIRADO)
        ->and($lastMonth->refresh()->status)->toBe(AttendanceStatus::RETIRADO);
});

test('attendances:clean leaves kids that are still inside alone', function () {
    $kid = Kid::factory()->create();

    $inside = attendanceFor($kid, [
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->artisan('attendances:clean')
        ->expectsOutput('Se marcaron 0 asistencias como RETIRADO.')
        ->assertSuccessful();

    expect($inside->refresh()->status)->toBe(AttendanceStatus::EN_CLASE);
});

test('attendances:clean does not touch attendances already retirado', function () {
    $kid = Kid::factory()->create();

    $retired = attendanceFor($kid, [
        'check_in' => now()->subHours(2),
        'check_out' => now()->subHour(),
        'status' => AttendanceStatus::RETIRADO,
    ]);
    $updatedAt = $retired->updated_at;

    $this->travel(1)->hours();

    $this->artisan('attendances:clean')
        ->expectsOutput('Se marcaron 0 asistencias como RETIRADO.')
        ->assertSuccessful();

    expect($retired->refresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});
