<?php

namespace Tests\Feature;

use App\Events\WhatsAppNotification;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\TutorMessage;
use App\Services\TutorMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TutorMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TutorMessageService $service;

    protected Contact $contact;

    protected Kid $kid;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_send_entry_message_dispatches_event(): void
    {
        $this->service->sendEntryMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_exit_message_dispatches_event(): void
    {
        $this->service->sendExitMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_welcome_message_dispatches_event(): void
    {
        $this->service->sendWelcomeMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_bathroom_message_dispatches_event(): void
    {
        $this->service->sendBathroomMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_diaper_message_dispatches_event(): void
    {
        $this->service->sendDiaperMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_sick_message_dispatches_event(): void
    {
        $this->service->sendSickMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_recovered_message_dispatches_event(): void
    {
        $this->service->sendRecoveredMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_unconsolable_message_dispatches_event(): void
    {
        $this->service->sendUnconsolableMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_assistance_message_dispatches_event(): void
    {
        $this->service->sendAssistanceMessage($this->contact, $this->kid);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    public function test_send_message_with_inactive_template_does_not_dispatch(): void
    {
        TutorMessage::where('label', 'entry')->update(['is_active' => false]);

        $this->service->sendEntryMessage($this->contact, $this->kid);

        Event::assertNotDispatched(WhatsAppNotification::class);
    }

    // Tests for WhatsApp URL generation methods

    public function test_get_welcome_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getWelcomeMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_get_entry_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getEntryMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_get_exit_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getExitMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_get_bathroom_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getBathroomMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_get_diaper_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getDiaperMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_get_sick_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getSickMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_get_recovered_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getRecoveredMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_get_unconsolable_message_url_returns_valid_whatsapp_url(): void
    {
        $url = $this->service->getUnconsolableMessageUrl($this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_generate_whatsapp_url_includes_contact_phone_number(): void
    {
        $url = $this->service->generateWhatsAppUrl('entry', $this->contact, $this->kid);

        $phoneNumber = preg_replace('/[^0-9]/', '', $this->contact->full_phone);
        $this->assertStringContainsString($phoneNumber, $url);
    }

    public function test_generate_whatsapp_url_replaces_tutor_tag_with_contact_name(): void
    {
        $url = $this->service->generateWhatsAppUrl('entry', $this->contact, $this->kid);

        $decodedMessage = urldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString($this->contact->full_name, $decodedMessage);
    }

    public function test_generate_whatsapp_url_replaces_nino_tag_with_kid_name(): void
    {
        $url = $this->service->generateWhatsAppUrl('exit', $this->contact, $this->kid);

        $decodedMessage = urldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString($this->kid->full_name, $decodedMessage);
    }

    public function test_generate_whatsapp_url_with_inactive_template_returns_empty_message_url(): void
    {
        TutorMessage::where('label', 'entry')->update(['is_active' => false]);

        $url = $this->service->generateWhatsAppUrl('entry', $this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_generate_whatsapp_url_with_nonexistent_label_returns_empty_message_url(): void
    {
        $url = $this->service->generateWhatsAppUrl('nonexistent', $this->contact, $this->kid);

        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('?text=', $url);
    }

    public function test_generate_whatsapp_url_strips_html_tags(): void
    {
        // Update message to include HTML tags
        TutorMessage::where('label', 'entry')->update([
            'message' => '<p>Hola [tutor], <strong>[nino]</strong> ha llegado.</p>',
        ]);

        $url = $this->service->generateWhatsAppUrl('entry', $this->contact, $this->kid);

        $decodedMessage = urldecode(parse_url($url, PHP_URL_QUERY));

        $this->assertStringNotContainsString('<p>', $decodedMessage);
        $this->assertStringNotContainsString('</p>', $decodedMessage);
        $this->assertStringNotContainsString('<strong>', $decodedMessage);
        $this->assertStringNotContainsString('</strong>', $decodedMessage);
        $this->assertStringContainsString($this->contact->full_name, $decodedMessage);
        $this->assertStringContainsString($this->kid->full_name, $decodedMessage);
    }
}
