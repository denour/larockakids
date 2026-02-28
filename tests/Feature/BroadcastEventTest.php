<?php

use App\Events\WhatsAppNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Event;

// =============================================================
// WhatsAppNotification Event Structure
// =============================================================

test('whatsapp notification event implements ShouldBroadcast', function () {
    $event = new WhatsAppNotification('Test message', '525551234567');

    expect($event)->toBeInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class);
});

test('whatsapp notification event stores message and phone number', function () {
    $event = new WhatsAppNotification('Hola Juan, María llegó.', '525551234567');

    expect($event->message)->toBe('Hola Juan, María llegó.')
        ->and($event->phoneNumber)->toBe('525551234567');
});

test('whatsapp notification broadcasts on correct channel', function () {
    $event = new WhatsAppNotification('Test', '525551234567');

    $channel = $event->broadcastOn();

    expect($channel)->toBeInstanceOf(Channel::class)
        ->and($channel->name)->toBe('whatsapp-notifications');
});

test('whatsapp notification broadcasts with correct event name', function () {
    $event = new WhatsAppNotification('Test', '525551234567');

    expect($event->broadcastAs())->toBe('notification');
});

test('whatsapp notification event is public channel not private', function () {
    $event = new WhatsAppNotification('Test', '525551234567');

    $channel = $event->broadcastOn();

    expect($channel)->toBeInstanceOf(Channel::class)
        ->and($channel)->not->toBeInstanceOf(\Illuminate\Broadcasting\PrivateChannel::class);
});

test('whatsapp notification payload contains message and phone', function () {
    $event = new WhatsAppNotification('Mensaje de prueba', '521234567890');

    // Public properties are auto-serialized as broadcast payload
    expect($event->message)->toBe('Mensaje de prueba')
        ->and($event->phoneNumber)->toBe('521234567890');
});

// =============================================================
// Broadcast Dispatch Integration
// =============================================================

test('whatsapp notification can be dispatched via broadcast', function () {
    Event::fake([WhatsAppNotification::class]);

    broadcast(new WhatsAppNotification('Test broadcast', '525551234567'));

    Event::assertDispatched(WhatsAppNotification::class, function ($event) {
        return $event->message === 'Test broadcast'
            && $event->phoneNumber === '525551234567';
    });
});

test('whatsapp notification dispatched with correct payload data', function () {
    Event::fake([WhatsAppNotification::class]);

    $message = 'Hola María, Carlos llegó el 28/02/2026 10:30 AM.';
    $phone = '525551234567';

    broadcast(new WhatsAppNotification($message, $phone));

    Event::assertDispatched(WhatsAppNotification::class, function ($event) use ($message, $phone) {
        return $event->message === $message
            && $event->phoneNumber === $phone
            && $event->broadcastOn()->name === 'whatsapp-notifications'
            && $event->broadcastAs() === 'notification';
    });
});

// =============================================================
// WhatsAppController Broadcast Endpoint
// =============================================================

test('test notification endpoint dispatches broadcast event', function () {
    Event::fake([WhatsAppNotification::class]);

    $response = $this->post('/test-notification');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Notification sent',
        ]);

    Event::assertDispatched(WhatsAppNotification::class, function ($event) {
        return $event->message === 'Este es un mensaje de prueba de La Roca Kids'
            && $event->phoneNumber === '526861729522';
    });
});

test('test notification endpoint returns json response', function () {
    Event::fake([WhatsAppNotification::class]);

    $response = $this->post('/test-notification');

    $response->assertOk()
        ->assertJsonStructure(['status', 'message']);
});

// =============================================================
// TutorMessageService Broadcast Integration
// =============================================================

test('tutor message service broadcasts with correct phone format', function () {
    Event::fake([WhatsAppNotification::class]);

    $contact = \App\Models\Contact::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'phone' => '5551234567',
        'international_code' => '52',
    ]);

    $kid = \App\Models\Kid::factory()->create([
        'first_name' => 'María',
        'last_name' => 'Pérez',
    ]);
    $kid->contacts()->sync([]);
    $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

    \App\Models\TutorMessage::create([
        'label' => 'entry',
        'name' => 'Entrada',
        'message' => 'Hola [tutor], [nino] ha llegado.',
        'description' => 'Test',
        'is_active' => true,
    ]);

    $service = app(\App\Services\TutorMessageService::class);
    $service->sendEntryMessage($contact, $kid);

    Event::assertDispatched(WhatsAppNotification::class, function ($event) {
        // Phone should include country code: 525551234567
        return $event->phoneNumber === '525551234567'
            && str_contains($event->message, 'Juan Pérez')
            && str_contains($event->message, 'María Pérez');
    });
});

test('tutor message service broadcasts on whatsapp-notifications channel', function () {
    Event::fake([WhatsAppNotification::class]);

    $contact = \App\Models\Contact::factory()->create([
        'phone' => '5551234567',
        'international_code' => '52',
    ]);

    $kid = \App\Models\Kid::factory()->create();
    $kid->contacts()->sync([]);
    $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

    \App\Models\TutorMessage::create([
        'label' => 'welcome',
        'name' => 'Bienvenida',
        'message' => 'Bienvenido [tutor].',
        'description' => 'Test',
        'is_active' => true,
    ]);

    $service = app(\App\Services\TutorMessageService::class);
    $service->sendWelcomeMessage($contact, $kid);

    Event::assertDispatched(WhatsAppNotification::class, function ($event) {
        return $event->broadcastOn()->name === 'whatsapp-notifications'
            && $event->broadcastAs() === 'notification';
    });
});
