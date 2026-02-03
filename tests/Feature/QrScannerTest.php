<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\QrCodeStatus;
use App\Events\WhatsAppNotification;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\QrCode;
use App\Models\TutorMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class QrScannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TutorMessage::create([
            'label' => 'entry',
            'name' => 'Entrada',
            'message' => 'Hola [tutor], [nino] ha llegado.',
            'description' => 'Mensaje de entrada',
            'is_active' => true,
        ]);

        TutorMessage::create([
            'label' => 'exit',
            'name' => 'Salida',
            'message' => 'Hola [tutor], [nino] ha salido.',
            'description' => 'Mensaje de salida',
            'is_active' => true,
        ]);

        TutorMessage::create([
            'label' => 'assistance',
            'name' => 'Asistencia',
            'message' => 'Hola [tutor], tu hijo(a) [nino] necesita asistencia.',
            'description' => 'Mensaje de asistencia',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function check_in_page_loads_successfully(): void
    {
        $response = $this->get(route('scanner.check-in'));

        $response->assertStatus(200);
        $response->assertSee('ENTRADA');
    }

    /** @test */
    public function check_out_page_loads_successfully(): void
    {
        $response = $this->get(route('scanner.check-out'));

        $response->assertStatus(200);
        $response->assertSee('SALIDA');
    }

    /** @test */
    public function check_in_creates_attendance_for_assigned_qr(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        $qrCode = QrCode::factory()->create([
            'code' => 'TEST-0001',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'TEST-0001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'check_in',
            'kid_name' => $kid->full_name,
        ]);

        $this->assertDatabaseHas('attendances', [
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'status' => AttendanceStatus::EN_CLASE->value,
        ]);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function check_in_sends_assistance_when_already_checked_in(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        $qrCode = QrCode::factory()->create([
            'code' => 'TEST-0002',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'TEST-0002',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'assistance',
        ]);

        $this->assertDatabaseCount('attendances', 1);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function check_in_fails_for_unassigned_qr_code(): void
    {
        Event::fake([WhatsAppNotification::class]);

        QrCode::factory()->create([
            'code' => 'TEST-0003',
            'kid_id' => null,
            'status' => QrCodeStatus::Available,
        ]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'TEST-0003',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'Este código QR no está asignado a ningún niño.']);

        $this->assertDatabaseCount('attendances', 0);
        Event::assertNotDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function check_in_fails_for_invalid_qr_code(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'INVALID-CODE',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'Código QR no encontrado.']);

        Event::assertNotDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function check_out_updates_attendance(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        $qrCode = QrCode::factory()->create([
            'code' => 'TEST-0004',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $attendance = Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $response = $this->postJson(route('scanner.check-out.process'), [
            'code' => 'TEST-0004',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'check_out',
            'kid_name' => $kid->full_name,
        ]);

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out);
        $this->assertEquals(AttendanceStatus::RETIRADO, $attendance->status);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function check_out_fails_when_not_checked_in(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        $qrCode = QrCode::factory()->create([
            'code' => 'TEST-0005',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $response = $this->postJson(route('scanner.check-out.process'), [
            'code' => 'TEST-0005',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'No hay entrada registrada para '.$kid->full_name.' hoy.']);

        Event::assertNotDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function check_in_validates_code_is_required(): void
    {
        $response = $this->postJson(route('scanner.check-in.process'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function check_out_validates_code_is_required(): void
    {
        $response = $this->postJson(route('scanner.check-out.process'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function check_in_prioritizes_parent_contact(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $parentContact = Contact::factory()->create();
        $guardianContact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($guardianContact->id, ['relationship_type' => 'guardian']);
        $kid->contacts()->attach($parentContact->id, ['relationship_type' => 'parent']);

        $qrCode = QrCode::factory()->create([
            'code' => 'TEST-0006',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'TEST-0006',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'check_in',
        ]);

        $this->assertDatabaseHas('attendances', [
            'kid_id' => $kid->id,
            'contact_id' => $parentContact->id,
        ]);
    }

    /** @test */
    public function check_in_stores_client_ip(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        QrCode::factory()->create([
            'code' => 'TEST-0007',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'TEST-0007',
        ], ['REMOTE_ADDR' => '192.168.1.100']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'check_in',
        ]);

        $this->assertDatabaseHas('attendances', [
            'kid_id' => $kid->id,
            'check_in_ip' => '192.168.1.100',
        ]);
    }

    /** @test */
    public function check_out_fails_when_ip_does_not_match(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        QrCode::factory()->create([
            'code' => 'TEST-0008',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'check_in_ip' => '192.168.1.100',
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $response = $this->postJson(route('scanner.check-out.process'), [
            'code' => 'TEST-0008',
        ], ['REMOTE_ADDR' => '192.168.1.200']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'Error de autenticación: esta acción debe realizarse desde el dispositivo original.']);

        Event::assertNotDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function check_out_succeeds_when_ip_matches(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        QrCode::factory()->create([
            'code' => 'TEST-0009',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'check_in_ip' => '192.168.1.100',
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $response = $this->postJson(route('scanner.check-out.process'), [
            'code' => 'TEST-0009',
        ], ['REMOTE_ADDR' => '192.168.1.100']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'check_out',
        ]);

        Event::assertDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function assistance_message_fails_when_ip_does_not_match(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        QrCode::factory()->create([
            'code' => 'TEST-0010',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'check_in_ip' => '192.168.1.100',
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'TEST-0010',
        ], ['REMOTE_ADDR' => '192.168.1.200']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'Error de autenticación: esta acción debe realizarse desde el dispositivo original.']);

        Event::assertNotDispatched(WhatsAppNotification::class);
    }

    /** @test */
    public function assistance_message_succeeds_when_ip_matches(): void
    {
        Event::fake([WhatsAppNotification::class]);

        $contact = Contact::factory()->create();
        $kid = Kid::factory()->create();
        $kid->contacts()->sync([]);
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        QrCode::factory()->create([
            'code' => 'TEST-0011',
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'check_in_ip' => '192.168.1.100',
            'status' => AttendanceStatus::EN_CLASE,
        ]);

        $response = $this->postJson(route('scanner.check-in.process'), [
            'code' => 'TEST-0011',
        ], ['REMOTE_ADDR' => '192.168.1.100']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'assistance',
        ]);

        Event::assertDispatched(WhatsAppNotification::class);
    }
}
