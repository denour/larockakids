<?php

use App\Models\Contact;
use App\Models\Kid;
use Illuminate\Support\Facades\DB;
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

test('it requires phone at the database level', function () {
    // Unlike first_name/last_name, a null phone never reaches the database:
    // Contact::booted() runs preg_replace() over $contact->phone on every save
    // and preg_replace(null) returns '', so the model writes an empty string
    // that satisfies the NOT NULL column. Bypass the model to assert the
    // constraint the migration actually declares.
    DB::table('contacts')->insert([
        'first_name' => 'Rosa',
        'last_name' => 'Lopez',
        'phone' => null,
        'international_code' => '52',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(\Illuminate\Database\QueryException::class);

test('the saving hook turns a null phone into an empty string', function () {
    // Pins the behaviour described above. It is arguably a defect of its own:
    // a contact with no phone is silently accepted even though phone is
    // "required", and that tutor's WhatsApp notifications will go nowhere.
    // Left as-is on purpose — changing it is a live-app behaviour change.
    $contact = Contact::factory()->create(['phone' => null]);

    expect($contact->phone)->toBe('')
        ->and(Contact::find($contact->id)->phone)->toBe('');
});

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
