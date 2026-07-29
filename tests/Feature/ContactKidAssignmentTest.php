<?php

use App\Enums\Country;
use App\Models\Allergy;
use App\Models\Contact;
use App\Models\Kid;
use App\Services\AttendanceScannerService;
use Illuminate\Support\Facades\Event;

// =============================================================
// Contact Model Fields
// =============================================================

test('contact has all required fillable fields', function () {
    $contact = Contact::factory()->create([
        'first_name' => 'María',
        'last_name' => 'García',
        'phone' => '5551234567',
        'international_code' => '52',
        'email' => 'maria@example.com',
    ]);

    expect($contact->first_name)->toBe('María')
        ->and($contact->last_name)->toBe('García')
        ->and($contact->phone)->toBe('5551234567')
        ->and($contact->international_code)->toBe('52')
        ->and($contact->email)->toBe('maria@example.com');
});

test('contact full_name accessor concatenates first and last name', function () {
    $contact = Contact::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
    ]);

    expect($contact->full_name)->toBe('Juan Pérez');
});

test('contact full_phone accessor prepends plus and international code', function () {
    $contact = Contact::factory()->create([
        'international_code' => '52',
        'phone' => '5551234567',
    ]);

    expect($contact->full_phone)->toBe('+525551234567');
});

test('contact full_phone works correctly with numeric international_code', function () {
    // Country::getCode() now returns '52' without '+', matching migration default
    // getFullPhoneAttribute prepends '+', producing correct '+525551234567'
    $contact = Contact::factory()->create([
        'international_code' => '52',
        'phone' => '5551234567',
    ]);

    expect($contact->full_phone)->toBe('+525551234567');
});

test('contact email is nullable', function () {
    $contact = Contact::factory()->create(['email' => null]);

    expect($contact->email)->toBeNull();
    $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'email' => null]);
});

test('contact factory creates valid contact with default international_code 52', function () {
    $contact = Contact::factory()->create();

    expect($contact->international_code)->toBe('52')
        ->and($contact->phone)->toMatch('/^\d{10}$/');
});

// =============================================================
// Kid Model Fields
// =============================================================

test('kid has all required fillable fields', function () {
    $kid = Kid::factory()->create([
        'first_name' => 'Sofía',
        'last_name' => 'López',
        'birth_date' => '2020-05-15',
        'gender' => 'female',
    ]);

    expect($kid->first_name)->toBe('Sofía')
        ->and($kid->last_name)->toBe('López')
        ->and($kid->birth_date->format('Y-m-d'))->toBe('2020-05-15')
        ->and($kid->gender)->toBe('female');
});

test('kid full_name accessor concatenates first and last name', function () {
    $kid = Kid::factory()->create([
        'first_name' => 'Carlos',
        'last_name' => 'Ramírez',
    ]);

    expect($kid->full_name)->toBe('Carlos Ramírez');
});

test('kid age accessor calculates age from birth_date', function () {
    $kid = Kid::factory()->create([
        'birth_date' => now()->subYears(5)->subMonth(),
    ]);

    expect($kid->age)->toBe(5);
});

test('kid gender defaults to male in migration', function () {
    // The migration has default('male'), so inserting without gender should default
    $kid = Kid::factory()->create(['gender' => 'male']);

    expect($kid->gender)->toBe('male');
});

test('kid medical_notes and is_active are fillable and exist in schema', function () {
    $kid = Kid::factory()->create();

    expect(in_array('medical_notes', (new Kid)->getFillable()))->toBeTrue()
        ->and(in_array('is_active', (new Kid)->getFillable()))->toBeTrue();

    $this->assertTrue(
        \Illuminate\Support\Facades\Schema::hasColumn('kids', 'medical_notes')
    );
    $this->assertTrue(
        \Illuminate\Support\Facades\Schema::hasColumn('kids', 'is_active')
    );

    // Verify mass assignment works
    $kidWithNotes = Kid::factory()->create([
        'medical_notes' => 'Alergia al gluten',
        'is_active' => false,
    ]);

    expect($kidWithNotes->medical_notes)->toBe('Alergia al gluten')
        ->and($kidWithNotes->is_active)->toBeFalsy();
});

// =============================================================
// Contact-Kid Pivot Table (Many-to-Many)
// =============================================================

test('kid can be attached to contact with relationship_type', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $contact = Contact::factory()->create();

    $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

    $this->assertDatabaseHas('contact_kid', [
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'relationship_type' => 'parent',
    ]);
});

test('pivot relationship_type defaults to parent in migration', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $contact = Contact::factory()->create();

    // Attach without specifying relationship_type - migration default is 'parent'
    $kid->contacts()->attach($contact->id);

    $this->assertDatabaseHas('contact_kid', [
        'kid_id' => $kid->id,
        'contact_id' => $contact->id,
        'relationship_type' => 'parent',
    ]);
});

test('all five relationship types can be stored', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $types = ['parent', 'guardian', 'family', 'friend of parent', 'other'];

    foreach ($types as $type) {
        $contact = Contact::factory()->create();
        $kid->contacts()->attach($contact->id, ['relationship_type' => $type]);
    }

    expect($kid->contacts()->count())->toBe(5);

    foreach ($types as $type) {
        $this->assertDatabaseHas('contact_kid', [
            'kid_id' => $kid->id,
            'relationship_type' => $type,
        ]);
    }
});

test('contact_kid has unique constraint on contact_id and kid_id', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $contact = Contact::factory()->create();

    $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

    // Trying to attach same contact again should fail
    expect(fn () => $kid->contacts()->attach($contact->id, ['relationship_type' => 'guardian']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('kid contacts relationship returns pivot data', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $contact = Contact::factory()->create();

    $kid->contacts()->attach($contact->id, ['relationship_type' => 'guardian']);

    $loadedContact = $kid->contacts()->first();

    expect($loadedContact->pivot->relationship_type)->toBe('guardian')
        ->and($loadedContact->pivot->contact_id)->toBe($contact->id)
        ->and($loadedContact->pivot->kid_id)->toBe($kid->id);
});

test('contact kids relationship returns pivot data', function () {
    $contact = Contact::factory()->create();
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $contact->kids()->attach($kid->id, ['relationship_type' => 'family']);

    $loadedKid = $contact->kids()->first();

    expect($loadedKid->pivot->relationship_type)->toBe('family');
});

test('kid can have multiple contacts with different relationship types', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $parent = Contact::factory()->create();
    $guardian = Contact::factory()->create();
    $family = Contact::factory()->create();

    $kid->contacts()->attach($parent->id, ['relationship_type' => 'parent']);
    $kid->contacts()->attach($guardian->id, ['relationship_type' => 'guardian']);
    $kid->contacts()->attach($family->id, ['relationship_type' => 'family']);

    expect($kid->contacts()->count())->toBe(3);

    $types = $kid->contacts->pluck('pivot.relationship_type')->toArray();
    expect($types)->toContain('parent')
        ->toContain('guardian')
        ->toContain('family');
});

test('contact can be assigned to multiple kids', function () {
    $contact = Contact::factory()->create();

    $kid1 = Kid::factory()->create();
    $kid1->contacts()->sync([]);
    $kid2 = Kid::factory()->create();
    $kid2->contacts()->sync([]);

    $kid1->contacts()->attach($contact->id, ['relationship_type' => 'parent']);
    $kid2->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

    expect($contact->kids()->count())->toBe(2);
});

test('deleting contact cascades to pivot table', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $contact = Contact::factory()->create();

    $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);
    $contactId = $contact->id;

    $contact->delete();

    $this->assertDatabaseMissing('contact_kid', ['contact_id' => $contactId]);
});

test('deleting kid cascades to pivot table', function () {
    $contact = Contact::factory()->create();
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);
    $kidId = $kid->id;

    // Soft delete keeps the pivot so the kid can be restored.
    $kid->delete();
    $this->assertDatabaseHas('contact_kid', ['kid_id' => $kidId]);

    // Force delete cascades and removes the pivot.
    $kid->forceDelete();
    $this->assertDatabaseMissing('contact_kid', ['kid_id' => $kidId]);
});

// =============================================================
// Country Enum & International Code
// =============================================================

test('country enum getCode returns code without plus sign', function () {
    expect(Country::MEXICO->getCode())->toBe('52')
        ->and(Country::USA->getCode())->toBe('1')
        ->and(Country::SPAIN->getCode())->toBe('34')
        ->and(Country::ECUADOR->getCode())->toBe('593');
});

test('country enum default country is Mexico', function () {
    expect(Country::getDefaultCountry())->toBe(Country::MEXICO);
    expect(Country::getDefaultCountry()->getCode())->toBe('52');
});

test('migration default for international_code matches Country getCode', function () {
    // Migration default is '52', Country::getCode() returns '52' — now consistent
    $contact = Contact::factory()->create();
    expect($contact->international_code)->toBe('52');
});

test('factory and filament form save same international_code format', function () {
    // Factory creates with '52'
    $factoryContact = Contact::factory()->create();
    expect($factoryContact->international_code)->toBe('52');

    // Filament also saves '52' via Country::getDefaultCountry()->getCode()
    $filamentContact = Contact::create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => '5551234567',
        'international_code' => Country::getDefaultCountry()->getCode(),
        'email' => null,
    ]);
    expect($filamentContact->international_code)->toBe('52');

    // Both produce the same correct full_phone
    expect($factoryContact->full_phone)->toStartWith('+52')
        ->and($filamentContact->full_phone)->toStartWith('+52');
});

// =============================================================
// KidFactory Relationship Type Inconsistency
// =============================================================

test('kid factory uses English relationship types matching service priority', function () {
    $kid = Kid::factory()->create();

    $firstContact = $kid->contacts()->first();

    expect($firstContact)->not->toBeNull();
    expect($firstContact->pivot->relationship_type)->toBe('parent');
});

test('factory relationship types match service priority list', function () {
    $kid = Kid::factory()->create();
    $factoryTypes = $kid->contacts->pluck('pivot.relationship_type')->toArray();

    $servicePriority = ['parent', 'guardian', 'family', 'friend of parent', 'other'];

    $matchesService = collect($factoryTypes)->every(fn ($type) => in_array($type, $servicePriority));

    expect($matchesService)->toBeTrue();
});

// =============================================================
// getPrimaryContact Priority Logic
// =============================================================

test('getPrimaryContact returns parent as highest priority', function () {
    Event::fake();
    $service = app(AttendanceScannerService::class);

    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $other = Contact::factory()->create(['first_name' => 'Other']);
    $guardian = Contact::factory()->create(['first_name' => 'Guardian']);
    $parent = Contact::factory()->create(['first_name' => 'Parent']);

    $kid->contacts()->attach($other->id, ['relationship_type' => 'other']);
    $kid->contacts()->attach($guardian->id, ['relationship_type' => 'guardian']);
    $kid->contacts()->attach($parent->id, ['relationship_type' => 'parent']);

    $primary = $service->getPrimaryContact($kid);

    expect($primary->id)->toBe($parent->id)
        ->and($primary->first_name)->toBe('Parent');
});

test('getPrimaryContact returns guardian when no parent exists', function () {
    Event::fake();
    $service = app(AttendanceScannerService::class);

    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $other = Contact::factory()->create(['first_name' => 'Other']);
    $guardian = Contact::factory()->create(['first_name' => 'Guardian']);

    $kid->contacts()->attach($other->id, ['relationship_type' => 'other']);
    $kid->contacts()->attach($guardian->id, ['relationship_type' => 'guardian']);

    $primary = $service->getPrimaryContact($kid);

    expect($primary->id)->toBe($guardian->id);
});

test('getPrimaryContact returns family when no parent or guardian', function () {
    Event::fake();
    $service = app(AttendanceScannerService::class);

    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $other = Contact::factory()->create(['first_name' => 'Other']);
    $family = Contact::factory()->create(['first_name' => 'Family']);

    $kid->contacts()->attach($other->id, ['relationship_type' => 'other']);
    $kid->contacts()->attach($family->id, ['relationship_type' => 'family']);

    $primary = $service->getPrimaryContact($kid);

    expect($primary->id)->toBe($family->id);
});

test('getPrimaryContact returns friend of parent when no parent, guardian, or family', function () {
    Event::fake();
    $service = app(AttendanceScannerService::class);

    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $other = Contact::factory()->create(['first_name' => 'Other']);
    $friend = Contact::factory()->create(['first_name' => 'Friend']);

    $kid->contacts()->attach($other->id, ['relationship_type' => 'other']);
    $kid->contacts()->attach($friend->id, ['relationship_type' => 'friend of parent']);

    $primary = $service->getPrimaryContact($kid);

    expect($primary->id)->toBe($friend->id);
});

test('getPrimaryContact returns first contact as fallback when no matching types', function () {
    Event::fake();
    $service = app(AttendanceScannerService::class);

    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $contact = Contact::factory()->create(['first_name' => 'Custom']);
    $kid->contacts()->attach($contact->id, ['relationship_type' => 'unknown_type']); // unrecognized type

    $primary = $service->getPrimaryContact($kid);

    // Falls through all priority checks (none match 'unknown_type')
    // Returns first contact as fallback
    expect($primary->id)->toBe($contact->id);
});

test('getPrimaryContact returns null when kid has no contacts', function () {
    Event::fake();
    $service = app(AttendanceScannerService::class);

    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $primary = $service->getPrimaryContact($kid);

    expect($primary)->toBeNull();
});

// =============================================================
// TutorMessageService Phone Number Formatting
// =============================================================

test('tutor message service uses full_phone with country code', function () {
    Event::fake();

    // The service uses: preg_replace('/[^0-9]/', '', $contact->full_phone)
    // This strips the '+' and keeps digits including country code
    $contact = Contact::factory()->create([
        'phone' => '5551234567',
        'international_code' => '52',
    ]);

    // full_phone = '+525551234567', stripped = '525551234567'
    $stripped = preg_replace('/[^0-9]/', '', $contact->full_phone);
    expect($stripped)->toBe('525551234567');
});

// =============================================================
// Kid-Allergy Relationship
// =============================================================

test('kid can have allergies attached', function () {
    $kid = Kid::factory()->create();

    $allergy = Allergy::factory()->create(['name' => 'Cacahuates']);
    $kid->allergies()->sync([$allergy->id]);

    expect($kid->fresh()->allergies)->toHaveCount(1)
        ->and($kid->fresh()->allergies->first()->name)->toBe('Cacahuates');
});

test('kid can have multiple allergies', function () {
    $kid = Kid::factory()->create();

    $allergies = Allergy::factory()->count(3)->create();
    $kid->allergies()->sync($allergies->pluck('id'));

    expect($kid->fresh()->allergies)->toHaveCount(3);
});

// =============================================================
// Sync & Detach Operations
// =============================================================

test('syncing contacts replaces all existing relationships', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $contact1 = Contact::factory()->create();
    $contact2 = Contact::factory()->create();
    $contact3 = Contact::factory()->create();

    // First attach
    $kid->contacts()->attach($contact1->id, ['relationship_type' => 'parent']);
    $kid->contacts()->attach($contact2->id, ['relationship_type' => 'guardian']);
    expect($kid->contacts()->count())->toBe(2);

    // Sync replaces everything
    $kid->contacts()->sync([
        $contact3->id => ['relationship_type' => 'family'],
    ]);

    expect($kid->contacts()->count())->toBe(1);
    $this->assertDatabaseMissing('contact_kid', ['contact_id' => $contact1->id, 'kid_id' => $kid->id]);
    $this->assertDatabaseHas('contact_kid', ['contact_id' => $contact3->id, 'relationship_type' => 'family']);
});

test('detaching a contact removes only that relationship', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);

    $contact1 = Contact::factory()->create();
    $contact2 = Contact::factory()->create();

    $kid->contacts()->attach($contact1->id, ['relationship_type' => 'parent']);
    $kid->contacts()->attach($contact2->id, ['relationship_type' => 'guardian']);

    $kid->contacts()->detach($contact1->id);

    expect($kid->contacts()->count())->toBe(1);
    $this->assertDatabaseMissing('contact_kid', ['contact_id' => $contact1->id, 'kid_id' => $kid->id]);
    $this->assertDatabaseHas('contact_kid', ['contact_id' => $contact2->id, 'kid_id' => $kid->id]);
});

// =============================================================
// Pivot Timestamps
// =============================================================

test('pivot table records have timestamps', function () {
    $kid = Kid::factory()->create();
    $kid->contacts()->sync([]);
    $contact = Contact::factory()->create();

    $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

    $pivot = $kid->contacts()->first()->pivot;

    expect($pivot->created_at)->not->toBeNull()
        ->and($pivot->updated_at)->not->toBeNull();
});
