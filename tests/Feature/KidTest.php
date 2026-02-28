<?php

use App\Models\Contact;
use App\Models\Kid;
use Carbon\Exceptions\InvalidFormatException;

test('it can create a kid', function () {
    $kid = Kid::factory()->create();

    $this->assertDatabaseHas('kids', [
        'id' => $kid->id,
        'first_name' => $kid->first_name,
        'last_name' => $kid->last_name,
    ]);

    expect($kid->birth_date)->not->toBeNull();
});

test('it requires first name', function () {
    Kid::factory()->create(['first_name' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('it requires last name', function () {
    Kid::factory()->create(['last_name' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('it requires birth date', function () {
    Kid::factory()->create(['birth_date' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('birth date must be a valid date', function () {
    Kid::factory()->create(['birth_date' => 'invalid-date']);
})->throws(InvalidFormatException::class);

test('it can update a kid', function () {
    $kid = Kid::factory()->create();
    $newFirstName = fake()->firstName;

    $kid->update(['first_name' => $newFirstName]);

    expect($kid->fresh()->first_name)->toBe($newFirstName);
});

test('it can delete a kid', function () {
    $kid = Kid::factory()->create();

    $kid->delete();

    $this->assertDatabaseMissing('kids', ['id' => $kid->id]);
});

test('it has at least one contact after creation', function () {
    $kid = Kid::factory()->create();

    expect($kid->contacts()->count())->toBeGreaterThan(0);
});

test('it can have multiple contacts', function () {
    $kid = Kid::factory()->create();
    $existingContactsCount = $kid->contacts()->count();
    $newContacts = Contact::factory()->count(3)->create();

    $kid->contacts()->attach($newContacts, ['relationship_type' => 'parent']);

    expect($kid->fresh()->contacts)->toHaveCount($existingContactsCount + 3);
});

test('it can remove a contact', function () {
    $kid = Kid::factory()->create();
    $existingContactsCount = $kid->contacts()->count();
    $contact = Contact::factory()->create();

    $kid->contacts()->attach($contact, ['relationship_type' => 'parent']);
    expect($kid->fresh()->contacts)->toHaveCount($existingContactsCount + 1);

    $kid->contacts()->detach($contact);
    expect($kid->fresh()->contacts)->toHaveCount($existingContactsCount);
});

test('first contact is always a parent', function () {
    $kid = Kid::factory()->create();
    $firstContact = $kid->contacts->first();

    expect($firstContact->pivot->relationship_type)->toBe('parent');
});

test('it can update relationship type', function () {
    $kid = Kid::factory()->create();
    $contact = $kid->contacts->first();

    $kid->contacts()->updateExistingPivot($contact->id, ['relationship_type' => 'Tío']);

    expect(
        $kid->fresh()->contacts()->where('contact_id', $contact->id)->first()->pivot->relationship_type
    )->toBe('Tío');
});

test('kid has full name accessor', function () {
    $kid = Kid::factory()->create(['first_name' => 'María', 'last_name' => 'Pérez']);

    expect($kid->full_name)->toBe('María Pérez');
});

test('kid has age accessor', function () {
    $kid = Kid::factory()->create(['birth_date' => now()->subYears(5)]);

    expect($kid->age)->toBe(5);
});

test('kid has allergies relationship', function () {
    $kid = Kid::factory()->create();

    expect($kid->allergies)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('kid has attendances relationship', function () {
    $kid = Kid::factory()->create();

    expect($kid->attendances)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('kid has qr code relationship', function () {
    $kid = Kid::factory()->create();
    \App\Models\QrCode::factory()->assigned($kid)->create();

    expect($kid->fresh()->qrCode)->toBeInstanceOf(\App\Models\QrCode::class);
});
