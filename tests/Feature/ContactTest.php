<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Kid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_create_a_contact()
    {
        $contact = Contact::factory()->create();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'phone' => $contact->phone,
            'international_code' => $contact->international_code,
            'email' => $contact->email,
        ]);
    }

    /** @test */
    public function it_requires_first_name()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Contact::factory()->create([
            'first_name' => null,
        ]);
    }

    /** @test */
    public function it_requires_last_name()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Contact::factory()->create([
            'last_name' => null,
        ]);
    }

    /** @test */
    public function it_requires_phone()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Contact::factory()->create([
            'phone' => null,
        ]);
    }

    /** @test */
    public function email_is_optional()
    {
        $contact = Contact::factory()->create([
            'email' => null,
        ]);

        $this->assertNull($contact->email);
    }

    /** @test */
    public function email_must_be_valid_when_provided()
    {
        $validator = Validator::make(
            ['email' => 'invalid-email'],
            ['email' => Contact::rules()['email']]
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function valid_email_passes_validation()
    {
        $validator = Validator::make(
            ['email' => 'test@example.com'],
            ['email' => Contact::rules()['email']]
        );

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function it_has_a_full_name_accessor()
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $contact->full_name);
    }

    /** @test */
    public function it_has_a_full_phone_accessor()
    {
        $contact = Contact::factory()->create([
            'phone' => '1234567890',
            'international_code' => '52',
        ]);

        $this->assertEquals('+521234567890', $contact->full_phone);
    }

    /** @test */
    public function it_can_update_a_contact()
    {
        $contact = Contact::factory()->create();
        $newFirstName = $this->faker->firstName;

        $contact->update(['first_name' => $newFirstName]);

        $this->assertEquals($newFirstName, $contact->fresh()->first_name);
    }

    /** @test */
    public function it_can_delete_a_contact()
    {
        $contact = Contact::factory()->create();

        $contact->delete();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_can_have_multiple_kids()
    {
        $contact = Contact::factory()->create();
        $kids = Kid::factory()->count(3)->create();

        // Sync to avoid duplicates (factory already attaches contacts)
        $contact->kids()->syncWithoutDetaching($kids->pluck('id'));

        $this->assertTrue($contact->fresh()->kids->pluck('id')->contains($kids[0]->id));
        $this->assertTrue($contact->fresh()->kids->pluck('id')->contains($kids[1]->id));
        $this->assertTrue($contact->fresh()->kids->pluck('id')->contains($kids[2]->id));
    }

    /** @test */
    public function it_can_remove_a_kid()
    {
        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();

        // Sync to avoid duplicates (factory already attaches contacts)
        $contact->kids()->syncWithoutDetaching([$kid->id]);
        $initialCount = $contact->fresh()->kids->count();
        $this->assertGreaterThanOrEqual(1, $initialCount);

        $contact->kids()->detach($kid);
        $this->assertCount($initialCount - 1, $contact->fresh()->kids);
    }
}
