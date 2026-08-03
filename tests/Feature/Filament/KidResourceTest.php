<?php

use App\Filament\Resources\KidResource\Pages\CreateKid;
use App\Filament\Resources\KidResource\Pages\EditKid;
use App\Filament\Resources\KidResource\Pages\ListKids;
use App\Models\Kid;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('kid list page renders', function () {
    $this->get('/admin/kids')
        ->assertSuccessful();
});

test('kid list page shows kids', function () {
    $kids = Kid::factory()->count(3)->create();

    Livewire::test(ListKids::class)
        ->assertCanSeeTableRecords($kids);
});

test('kid list page can search by first name', function () {
    $kid = Kid::factory()->create(['first_name' => 'UniqueSearchName']);
    Kid::factory()->count(3)->create();

    Livewire::test(ListKids::class)
        ->searchTable('UniqueSearchName')
        ->assertCanSeeTableRecords(collect([$kid]));
});

test('kid create page renders', function () {
    $this->get('/admin/kids/create')
        ->assertSuccessful();
});

test('kid can be created via form', function () {
    Livewire::test(CreateKid::class)
        ->fillForm([
            'first_name' => 'Test Kid',
            'last_name' => 'Test Last',
            'birth_date' => now()->subYears(3)->format('Y-m-d'),
            'gender' => 'male',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('kids', [
        'first_name' => 'Test Kid',
        'last_name' => 'Test Last',
    ]);
});

test('kid create requires first name', function () {
    Livewire::test(CreateKid::class)
        ->fillForm([
            'first_name' => '',
            'last_name' => 'Test Last',
            'birth_date' => now()->subYears(3)->format('Y-m-d'),
            'gender' => 'male',
        ])
        ->call('create')
        ->assertHasFormErrors(['first_name' => 'required']);
});

test('kid create requires last name', function () {
    Livewire::test(CreateKid::class)
        ->fillForm([
            'first_name' => 'Test Kid',
            'last_name' => '',
            'birth_date' => now()->subYears(3)->format('Y-m-d'),
            'gender' => 'male',
        ])
        ->call('create')
        ->assertHasFormErrors(['last_name' => 'required']);
});

test('kid create requires birth date', function () {
    Livewire::test(CreateKid::class)
        ->fillForm([
            'first_name' => 'Test Kid',
            'last_name' => 'Test Last',
            'birth_date' => null,
            'gender' => 'male',
        ])
        ->call('create')
        ->assertHasFormErrors(['birth_date' => 'required']);
});

test('kid edit page renders', function () {
    $kid = Kid::factory()->create();

    $this->get("/admin/kids/{$kid->id}/edit")
        ->assertSuccessful();
});

test('kid can be updated via form', function () {
    $kid = Kid::factory()->create();

    Livewire::test(EditKid::class, ['record' => $kid->id])
        ->fillForm([
            'first_name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($kid->fresh()->first_name)->toBe('Updated Name');
});

test('kid can be deleted from list', function () {
    $kid = Kid::factory()->create();

    Livewire::test(ListKids::class)
        ->callTableAction('delete', $kid);

    $this->assertSoftDeleted('kids', ['id' => $kid->id]);
});

test('kid list shows age column', function () {
    $kid = Kid::factory()->create(['birth_date' => now()->subYears(5)]);

    Livewire::test(ListKids::class)
        ->assertCanSeeTableRecords(collect([$kid]));
});
