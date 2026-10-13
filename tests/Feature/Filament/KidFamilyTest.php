<?php

use App\Filament\Resources\KidResource;
use App\Filament\Resources\KidResource\Pages\EditKid;
use App\Filament\Resources\KidResource\RelationManagers\ContactsRelationManager;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function contactsManager(Kid $kid): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(ContactsRelationManager::class, [
        'ownerRecord' => $kid,
        'pageClass' => EditKid::class,
    ]);
}

/**
 * @return array<int, string>
 */
function relationshipsOf(Kid $kid): array
{
    return $kid->contacts()->get()
        ->mapWithKeys(fn (Contact $contact) => [$contact->id => $contact->pivot->relationship_type])
        ->all();
}

// =============================================================
// Asignar un contacto que ya existe
// =============================================================

test('an existing contact can be assigned to a kid with its relationship', function () {
    ['kid' => $older, 'contact' => $mom] = createKidWithContact();
    ['kid' => $younger] = createKidWithContact();

    contactsManager($younger)
        ->callTableAction('attach', data: [
            'recordId' => $mom->id,
            'relationship_type' => 'guardian',
        ])
        ->assertHasNoTableActionErrors();

    expect(relationshipsOf($younger))->toHaveKey($mom->id, 'guardian')
        ->and(Contact::count())->toBe(2)
        ->and(relationshipsOf($older))->toHaveKey($mom->id, 'parent');
});

test('assigning an existing contact requires the relationship', function () {
    ['contact' => $mom] = createKidWithContact();
    ['kid' => $kid] = createKidWithContact();

    contactsManager($kid)
        ->callTableAction('attach', data: [
            'recordId' => $mom->id,
            'relationship_type' => null,
        ])
        ->assertHasTableActionErrors(['relationship_type' => 'required']);

    expect(relationshipsOf($kid))->not->toHaveKey($mom->id);
});

// =============================================================
// Quitar no borra el contacto
// =============================================================

test('removing a contact from one kid keeps it for the siblings', function () {
    ['kid' => $older, 'contact' => $mom] = createKidWithContact();
    ['kid' => $younger] = createKidWithContact();
    $younger->contacts()->attach($mom->id, ['relationship_type' => 'parent']);

    contactsManager($younger)
        ->callTableAction('detach', $mom);

    expect(Contact::find($mom->id))->not->toBeNull()
        ->and(relationshipsOf($younger))->not->toHaveKey($mom->id)
        ->and(relationshipsOf($older))->toHaveKey($mom->id);
});

test('the contacts table no longer offers to delete a contact', function () {
    ['kid' => $kid, 'contact' => $mom] = createKidWithContact();

    contactsManager($kid)
        ->assertTableActionDoesNotExist('delete')
        ->assertTableActionExists('detach')
        ->assertTableBulkActionDoesNotExist('delete')
        ->assertTableBulkActionExists('detach');
});

// =============================================================
// Hermanos
// =============================================================

test('siblings are the kids that share a contact', function () {
    ['kid' => $kid, 'contact' => $mom] = createKidWithContact();
    ['kid' => $sibling] = createKidWithContact();
    $sibling->contacts()->attach($mom->id, ['relationship_type' => 'parent']);
    ['kid' => $stranger] = createKidWithContact();

    expect($kid->siblings()->modelKeys())->toBe([$sibling->id])
        ->and($sibling->siblings()->modelKeys())->toBe([$kid->id])
        ->and($stranger->siblings())->toBeEmpty();
});

test('a kid without contacts has no siblings', function () {
    ['kid' => $kid] = createKidWithContact();
    $kid->contacts()->detach();

    expect($kid->siblings())->toBeEmpty();
});

test('the edit page lists the siblings', function () {
    ['kid' => $kid, 'contact' => $mom] = createKidWithContact();
    ['kid' => $sibling] = createKidWithContact();
    $sibling->contacts()->attach($mom->id, ['relationship_type' => 'parent']);
    ['kid' => $stranger] = createKidWithContact();

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->assertSee($sibling->full_name)
        ->assertSee(KidResource::getUrl('edit', ['record' => $sibling]))
        ->assertDontSee($stranger->full_name);
});

test('the edit page says when there are no siblings', function () {
    ['kid' => $kid] = createKidWithContact();

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->assertSee('Sin hermanos registrados.');
});

test('adding a sibling creates the kid with the same contacts and relationships', function () {
    ['kid' => $kid, 'contact' => $mom] = createKidWithContact();
    $dad = Contact::factory()->create();
    $kid->contacts()->attach($dad->id, ['relationship_type' => 'guardian']);

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->callAction('addSibling', data: [
            'first_name' => 'Sofía',
            'last_name' => $kid->last_name,
            'birth_date' => '2022-05-10',
            'gender' => 'female',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $sibling = Kid::where('first_name', 'Sofía')->sole();

    expect(relationshipsOf($sibling))->toEqual([$mom->id => 'parent', $dad->id => 'guardian'])
        ->and(Contact::count())->toBe(2)
        ->and($kid->siblings()->modelKeys())->toBe([$sibling->id]);
});

test('adding a sibling suggests the same last name', function () {
    ['kid' => $kid] = createKidWithContact(['last_name' => 'García López']);

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->mountAction('addSibling')
        ->assertActionDataSet(['last_name' => 'García López']);
});

test('adding a sibling requires name and birth date', function () {
    ['kid' => $kid] = createKidWithContact();

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->callAction('addSibling', data: ['first_name' => '', 'birth_date' => null])
        ->assertHasActionErrors(['first_name' => 'required', 'birth_date' => 'required']);

    expect(Kid::count())->toBe(1);
});

test('adding a sibling is hidden for a kid without contacts', function () {
    ['kid' => $kid] = createKidWithContact();
    $kid->contacts()->detach();

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->assertActionHidden('addSibling');
});

test('linking an existing kid as sibling shares the contacts both ways', function () {
    ['kid' => $kid, 'contact' => $mom] = createKidWithContact();
    ['kid' => $sibling, 'contact' => $dad] = createKidWithContact();

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->callAction('linkSibling', data: ['sibling_id' => $sibling->id])
        ->assertHasNoActionErrors()
        ->assertRedirect(KidResource::getUrl('edit', ['record' => $kid]));

    expect(array_keys(relationshipsOf($kid)))->toEqualCanonicalizing([$mom->id, $dad->id])
        ->and(array_keys(relationshipsOf($sibling)))->toEqualCanonicalizing([$mom->id, $dad->id])
        ->and($kid->siblings()->modelKeys())->toBe([$sibling->id]);
});

test('linking keeps the relationship a contact already had with the sibling', function () {
    ['kid' => $kid, 'contact' => $mom] = createKidWithContact();
    ['kid' => $sibling] = createKidWithContact();
    $sibling->contacts()->attach($mom->id, ['relationship_type' => 'guardian']);

    Livewire::test(EditKid::class, ['record' => $kid->getRouteKey()])
        ->callAction('linkSibling', data: ['sibling_id' => $sibling->id]);

    expect(relationshipsOf($sibling))->toHaveKey($mom->id, 'guardian');
});
