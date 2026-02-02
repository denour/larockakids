<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Kid;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class KidTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_create_a_kid()
    {
        $kid = Kid::factory()->create();

        $this->assertDatabaseHas('kids', [
            'id' => $kid->id,
            'first_name' => $kid->first_name,
            'last_name' => $kid->last_name,
            'birth_date' => $kid->birth_date,
        ]);
    }

    /** @test */
    public function it_requires_first_name()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Kid::factory()->create([
            'first_name' => null,
        ]);
    }

    /** @test */
    public function it_requires_last_name()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Kid::factory()->create([
            'last_name' => null,
        ]);
    }

    /** @test */
    public function it_requires_birth_date()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Kid::factory()->create([
            'birth_date' => null,
        ]);
    }

    /** @test */
    public function birth_date_must_be_a_valid_date()
    {
        $this->expectException(InvalidFormatException::class);

        Kid::factory()->create([
            'birth_date' => 'invalid-date',
        ]);
    }

    /** @test */
    public function it_can_update_a_kid()
    {
        $kid = Kid::factory()->create();
        $newFirstName = $this->faker->firstName;

        $kid->update(['first_name' => $newFirstName]);

        $this->assertEquals($newFirstName, $kid->fresh()->first_name);
    }

    /** @test */
    public function it_can_delete_a_kid()
    {
        $kid = Kid::factory()->create();

        $kid->delete();

        $this->assertDatabaseMissing('kids', [
            'id' => $kid->id,
        ]);
    }

    /** @test */
    public function it_has_at_least_one_contact_after_creation()
    {
        $kid = Kid::factory()->create();

        $this->assertGreaterThan(0, $kid->contacts()->count());
    }

    /** @test */
    public function it_can_have_multiple_contacts()
    {
        $kid = Kid::factory()->create();
        $existingContactsCount = $kid->contacts()->count();
        $newContacts = Contact::factory()->count(3)->create();

        $kid->contacts()->attach($newContacts, [
            'relationship_type' => 'parent',
        ]);

        $this->assertCount($existingContactsCount + 3, $kid->fresh()->contacts);
        $this->assertEquals(
            $newContacts->pluck('id')->sort()->values()->all(),
            $kid->fresh()->contacts->skip($existingContactsCount)->pluck('id')->sort()->values()->all()
        );
    }

    /** @test */
    public function it_can_remove_a_contact()
    {
        $kid = Kid::factory()->create();
        $existingContactsCount = $kid->contacts()->count();
        $contact = Contact::factory()->create();

        $kid->contacts()->attach($contact, [
            'relationship_type' => 'parent',
        ]);
        $this->assertCount($existingContactsCount + 1, $kid->fresh()->contacts);

        $kid->contacts()->detach($contact);
        $this->assertCount($existingContactsCount, $kid->fresh()->contacts);
    }

    /** @test */
    public function first_contact_is_always_a_parent()
    {
        $kid = Kid::factory()->create();
        $firstContact = $kid->contacts->first();

        $this->assertEquals('Padre/Madre', $firstContact->pivot->relationship_type);
    }

    /** @test */
    public function it_can_update_relationship_type()
    {
        $kid = Kid::factory()->create();
        $contact = $kid->contacts->first();

        $kid->contacts()->updateExistingPivot($contact->id, [
            'relationship_type' => 'family',
        ]);

        $this->assertEquals(
            'family',
            $kid->fresh()->contacts->first()->pivot->relationship_type
        );
    }
}
