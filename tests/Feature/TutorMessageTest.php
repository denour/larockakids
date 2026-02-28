<?php

use App\Models\TutorMessage;

test('it can create a tutor message', function () {
    $message = TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Hola [tutor], [nino] ha llegado.',
        'description' => 'Mensaje de entrada',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('tutor_messages', [
        'id' => $message->id,
        'label' => 'entry',
        'name' => 'Entrada',
    ]);
});

test('is active is cast to boolean', function () {
    $message = TutorMessage::create([
        'label' => 'exit',
        'name' => 'Salida',
        'message' => 'Hola [tutor].',
        'description' => 'Test',
        'is_active' => true,
    ]);

    expect($message->is_active)->toBeTrue()->toBeBool();
});

test('find by label returns active message', function () {
    TutorMessage::create([
        'label' => 'welcome',
        'name' => 'Bienvenida',
        'message' => 'Bienvenido [tutor].',
        'description' => 'Test',
        'is_active' => true,
    ]);

    $found = TutorMessage::findByLabel('welcome');

    expect($found)->not->toBeNull()
        ->and($found->label)->toBe('welcome');
});

test('find by label returns null for inactive message', function () {
    TutorMessage::create([
        'label' => 'bathroom',
        'name' => 'Baño',
        'message' => 'Fue al baño [nino].',
        'description' => 'Test',
        'is_active' => false,
    ]);

    $found = TutorMessage::findByLabel('bathroom');

    expect($found)->toBeNull();
});

test('find by label returns null for non existent label', function () {
    expect(TutorMessage::findByLabel('nonexistent'))->toBeNull();
});

test('replace tags replaces placeholders', function () {
    $message = TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Hola [tutor], [nino] llegó el [fecha] a las [hora].',
        'description' => 'Test',
        'is_active' => true,
    ]);

    // Reload from DB to ensure attributes are properly stored
    $message = $message->fresh();

    $result = $message->replaceTags([
        '[tutor]' => 'Juan',
        '[nino]' => 'María',
        '[fecha]' => '2026-01-01',
        '[hora]' => '10:00',
    ]);

    expect($result)
        ->toContain('Juan')
        ->toContain('María')
        ->toContain('2026-01-01')
        ->toContain('10:00');
});

test('soft delete works on tutor message', function () {
    $message = TutorMessage::create([
        'label' => 'exit',
        'name' => 'Salida',
        'message' => 'Test',
        'description' => 'Test',
        'is_active' => true,
    ]);

    $message->delete();

    $this->assertSoftDeleted('tutor_messages', ['id' => $message->id]);
    expect(TutorMessage::withTrashed()->find($message->id))->not->toBeNull();
});

test('it can update a tutor message', function () {
    $message = TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Old message',
        'description' => 'Test',
        'is_active' => true,
    ]);

    $message->update(['name' => 'Entrada Actualizada']);

    expect($message->fresh()->name)->toBe('Entrada Actualizada');
});
