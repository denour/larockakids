<?php

use App\Filament\Resources\AllergyResource\Pages\CreateAllergy;
use App\Filament\Resources\AllergyResource\Pages\EditAllergy;
use App\Filament\Resources\AllergyResource\Pages\ListAllergies;
use App\Models\Allergy;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('allergy list page renders', function () {
    $this->get('/admin/allergies')
        ->assertSuccessful();
});

test('allergy list shows records', function () {
    $allergies = Allergy::factory()->count(3)->create();

    Livewire::test(ListAllergies::class)
        ->assertCanSeeTableRecords($allergies);
});

test('allergy create page renders', function () {
    $this->get('/admin/allergies/create')
        ->assertSuccessful();
});

test('allergy can be created via form', function () {
    Livewire::test(CreateAllergy::class)
        ->fillForm([
            'name' => 'Maní',
            'color' => '#FF0000',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('allergies', ['name' => 'Maní']);
});

test('allergy create requires name', function () {
    Livewire::test(CreateAllergy::class)
        ->fillForm([
            'name' => '',
            'color' => '#FF0000',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('allergy edit page renders', function () {
    $allergy = Allergy::factory()->create();

    $this->get("/admin/allergies/{$allergy->id}/edit")
        ->assertSuccessful();
});

test('allergy can be updated via form', function () {
    $allergy = Allergy::factory()->create();

    Livewire::test(EditAllergy::class, ['record' => $allergy->id])
        ->fillForm(['name' => 'Gluten Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($allergy->fresh()->name)->toBe('Gluten Updated');
});

test('allergy can be deleted from list', function () {
    $allergy = Allergy::factory()->create();

    Livewire::test(ListAllergies::class)
        ->callTableAction('delete', $allergy);

    $this->assertDatabaseMissing('allergies', ['id' => $allergy->id]);
});
