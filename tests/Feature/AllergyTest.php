<?php

use App\Models\Allergy;
use App\Models\Kid;

test('it can create an allergy', function () {
    $allergy = Allergy::factory()->create();

    $this->assertDatabaseHas('allergies', [
        'id' => $allergy->id,
        'name' => $allergy->name,
        'color' => $allergy->color,
    ]);
});

test('it requires a name', function () {
    Allergy::factory()->create(['name' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('it requires a color', function () {
    Allergy::factory()->create(['color' => null]);
})->throws(\Illuminate\Database\QueryException::class);

test('it can update an allergy', function () {
    $allergy = Allergy::factory()->create();
    $allergy->update(['name' => 'Peanuts']);

    expect($allergy->fresh()->name)->toBe('Peanuts');
});

test('it can delete an allergy', function () {
    $allergy = Allergy::factory()->create();
    $allergy->delete();

    $this->assertDatabaseMissing('allergies', ['id' => $allergy->id]);
});

test('allergy has kids relationship', function () {
    $allergy = Allergy::factory()->create();

    expect($allergy->kids)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('allergy can be attached to kids', function () {
    $allergy = Allergy::factory()->create();
    $kid = Kid::factory()->create();

    $kid->allergies()->attach($allergy);

    expect($kid->fresh()->allergies->pluck('id'))->toContain($allergy->id);
});

test('allergy can be detached from kids', function () {
    $allergy = Allergy::factory()->create();
    $kid = Kid::factory()->create();

    $kid->allergies()->attach($allergy);
    $kid->allergies()->detach($allergy);

    expect($kid->fresh()->allergies->pluck('id'))->not->toContain($allergy->id);
});
