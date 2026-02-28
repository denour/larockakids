<?php

use App\Models\Contact;
use App\Models\Kid;
use Illuminate\Support\Facades\Validator;

test('it can create a contact', function () {
    $contact = Contact::factory()->create();

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'first_name' => $contact->first_name,
        'last_name' => $contact->last_name,
        'phone' => $contact->phone,
        'international_code' => $contact->international_code,
        'email' => $contact->email,
    ]);
});

test('it requires first name', function () {
    Contact::factory()->create(['first_name' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('it requires last name', function () {
    Contact::factory()->create(['last_name' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('it requires phone', function () {
    Contact::factory()->create(['phone' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('email is optional', function () {
    $contact = Contact::factory()->create(['email' => null]);

    expect($contact->email)->toBeNull();
});

test('email must be valid when provided', function () {
    $validator = Validator::make(
        ['email' => 'invalid-email'],
        ['email' => Contact::rules()['email']]
    );

    expect($validator->fails())->toBeTrue();
});

test('valid email passes validation', function () {
    $validator = Validator::make(
        ['email' => 'test@example.com'],
        ['email' => Contact::rules()['email']]
    );

    expect($validator->passes())->toBeTrue();
});

test('it has a full name accessor', function () {
    $contact = Contact::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($contact->full_name)->toBe('John Doe');
});

test('it has a full phone accessor', function () {
    $contact = Contact::factory()->create(['phone' => '1234567890', 'international_code' => '52']);

    expect($contact->full_phone)->toBe('+521234567890');
});

test('it can update a contact', function () {
    $contact = Contact::factory()->create();
    $newFirstName = fake()->firstName;

    $contact->update(['first_name' => $newFirstName]);

    expect($contact->fresh()->first_name)->toBe($newFirstName);
});

test('it can delete a contact', function () {
    $contact = Contact::factory()->create();

    $contact->delete();

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
});

test('it can have multiple kids', function () {
    $contact = Contact::factory()->create();
    $kids = Kid::factory()->count(3)->create();

    $contact->kids()->syncWithoutDetaching($kids->pluck('id'));

    expect($contact->fresh()->kids->pluck('id'))
        ->toContain($kids[0]->id)
        ->toContain($kids[1]->id)
        ->toContain($kids[2]->id);
});

test('it can remove a kid', function () {
    $contact = Contact::factory()->create();
    $kid = Kid::factory()->create();

    $contact->kids()->syncWithoutDetaching([$kid->id]);
    $initialCount = $contact->fresh()->kids->count();

    $contact->kids()->detach($kid);

    expect($contact->fresh()->kids)->toHaveCount($initialCount - 1);
});
