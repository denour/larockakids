<?php

use App\Events\WhatsAppNotification;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\TutorMessage;
use App\Services\TutorMessageService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake([WhatsAppNotification::class]);

    TutorMessage::create(['label' => 'entry', 'name' => 'Entrada', 'message' => 'Hola [tutor], [nino] ha llegado el [fecha] a las [hora].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'exit', 'name' => 'Salida', 'message' => 'Hola [tutor], [nino] ha salido.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'welcome', 'name' => 'Bienvenida', 'message' => 'Bienvenido [tutor], [nino] está registrado.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'bathroom', 'name' => 'Baño', 'message' => '[nino] fue al baño.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'diaper', 'name' => 'Pañal', 'message' => 'Se cambió pañal a [nino].', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'sick', 'name' => 'Enfermo', 'message' => '[nino] se siente mal.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'recovered', 'name' => 'Recuperado', 'message' => '[nino] se recuperó.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'unconsolable', 'name' => 'Inconsolable', 'message' => '[nino] está inconsolable.', 'description' => 'Test', 'is_active' => true]);
    TutorMessage::create(['label' => 'assistance', 'name' => 'Asistencia', 'message' => '[nino] necesita asistencia.', 'description' => 'Test', 'is_active' => true]);

    $this->service = app(TutorMessageService::class);
    $this->contact = Contact::factory()->create();
    $this->kid = Kid::factory()->create();
    $this->kid->contacts()->sync([]);
    $this->kid->contacts()->attach($this->contact->id, ['relationship_type' => 'parent']);
});

test('send entry message dispatches event', function () {
    $this->service->sendEntryMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send exit message dispatches event', function () {
    $this->service->sendExitMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send welcome message dispatches event', function () {
    $this->service->sendWelcomeMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send bathroom message dispatches event', function () {
    $this->service->sendBathroomMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send diaper message dispatches event', function () {
    $this->service->sendDiaperMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send sick message dispatches event', function () {
    $this->service->sendSickMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send recovered message dispatches event', function () {
    $this->service->sendRecoveredMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send unconsolable message dispatches event', function () {
    $this->service->sendUnconsolableMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send assistance message dispatches event', function () {
    $this->service->sendAssistanceMessage($this->contact, $this->kid);

    Event::assertDispatched(WhatsAppNotification::class);
});

test('send message with inactive template does not dispatch', function () {
    TutorMessage::where('label', 'entry')->update(['is_active' => false]);

    $this->service->sendEntryMessage($this->contact, $this->kid);

    // Should handle gracefully - no event dispatched when template is inactive
    Event::assertNotDispatched(WhatsAppNotification::class);
});
