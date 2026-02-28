<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;

test('it can create an attendance', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
    ]);
});

test('check in is cast to datetime', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
    ]);

    expect($attendance->check_in)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('check out is cast to datetime', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'check_out' => now()->addHours(2),
    ]);

    expect($attendance->check_out)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('status is cast to enum', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
        'status' => AttendanceStatus::EN_CLASE,
    ]);

    expect($attendance->status)->toBe(AttendanceStatus::EN_CLASE);
});

test('attendance belongs to kid', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
    ]);

    expect($attendance->kid)->toBeInstanceOf(Kid::class)
        ->and($attendance->kid->id)->toBe($kid->id);
});

test('attendance belongs to contact', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
    ]);

    expect($attendance->contact)->toBeInstanceOf(Contact::class)
        ->and($attendance->contact->id)->toBe($contact->id);
});

test('soft delete works on attendance', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
    ]);

    $attendance->delete();

    $this->assertSoftDeleted('attendances', ['id' => $attendance->id]);
    expect(Attendance::withTrashed()->find($attendance->id))->not->toBeNull();
});

test('attendance can be restored after soft delete', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $attendance = Attendance::create([
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'check_in' => now(),
    ]);

    $attendance->delete();
    $attendance->restore();

    $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    expect(Attendance::find($attendance->id))->not->toBeNull();
});

test('attendance factory creates valid data', function () {
    $attendance = Attendance::factory()->create();

    expect($attendance->kid)->not->toBeNull()
        ->and($attendance->contact)->not->toBeNull()
        ->and($attendance->check_in)->not->toBeNull();
});
