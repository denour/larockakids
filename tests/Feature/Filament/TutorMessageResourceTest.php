<?php

use App\Filament\Resources\TutorMessageResource\Pages\EditTutorMessage;
use App\Filament\Resources\TutorMessageResource\Pages\ListTutorMessages;
use App\Models\TutorMessage;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('tutor message list page renders', function () {
    $this->get('/admin/tutor-messages')
        ->assertSuccessful();
});

test('tutor message list shows records', function () {
    $message = TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Test',
        'description' => 'Test desc',
        'is_active' => true,
    ]);

    Livewire::test(ListTutorMessages::class)
        ->assertCanSeeTableRecords(collect([$message]));
});

test('tutor message edit page renders', function () {
    $message = TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Test',
        'description' => 'Test',
        'is_active' => true,
    ]);

    $this->get("/admin/tutor-messages/{$message->id}/edit")
        ->assertSuccessful();
});

test('tutor message can be updated via form', function () {
    $message = TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Test',
        'description' => 'Test',
        'is_active' => true,
    ]);

    Livewire::test(EditTutorMessage::class, ['record' => $message->id])
        ->fillForm(['name' => 'Entrada Actualizada'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($message->fresh()->name)->toBe('Entrada Actualizada');
});

test('tutor message can toggle active status', function () {
    $message = TutorMessage::create([
        'label' => 'exit',
        'name' => 'Salida',
        'message' => 'Test',
        'description' => 'Test',
        'is_active' => true,
    ]);

    Livewire::test(EditTutorMessage::class, ['record' => $message->id])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($message->fresh()->is_active)->toBeFalse();
});
