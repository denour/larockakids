<?php

namespace Tests\Feature;

use App\Enums\QrCodeStatus;
use App\Models\Kid;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[Test]
    public function it_can_create_a_qr_code(): void
    {
        $qrCode = QrCode::factory()->create([
            'code' => 'LRK-0001',
        ]);

        $this->assertDatabaseHas('qr_codes', [
            'id' => $qrCode->id,
            'code' => 'LRK-0001',
            'status' => QrCodeStatus::Available->value,
        ]);
    }

    #[Test]
    public function it_can_generate_a_batch_of_qr_codes(): void
    {
        $service = app(QrCodeService::class);
        $qrCodes = $service->generateBatch(5, 'TEST');

        $this->assertCount(5, $qrCodes);
        $this->assertEquals('TEST-0001', $qrCodes->first()->code);
        $this->assertEquals('TEST-0005', $qrCodes->last()->code);

        foreach ($qrCodes as $qrCode) {
            $this->assertDatabaseHas('qr_codes', [
                'id' => $qrCode->id,
                'status' => QrCodeStatus::Available->value,
            ]);
            $this->assertNotNull($qrCode->qr_image_path);
        }
    }

    #[Test]
    public function it_continues_sequence_when_generating_more_codes(): void
    {
        $service = app(QrCodeService::class);

        $service->generateBatch(3, 'SEQ');
        $secondBatch = $service->generateBatch(2, 'SEQ');

        $this->assertEquals('SEQ-0004', $secondBatch->first()->code);
        $this->assertEquals('SEQ-0005', $secondBatch->last()->code);
    }

    #[Test]
    public function it_can_assign_qr_code_to_kid(): void
    {
        $kid = Kid::factory()->create();
        $qrCode = QrCode::factory()->create();

        $this->assertTrue($qrCode->isAvailable());

        $qrCode->assignToKid($kid);

        $qrCode->refresh();

        $this->assertTrue($qrCode->isAssigned());
        $this->assertEquals($kid->id, $qrCode->kid_id);
        $this->assertNotNull($qrCode->assigned_at);
    }

    #[Test]
    public function it_can_mark_qr_code_as_lost(): void
    {
        $kid = Kid::factory()->create();
        $qrCode = QrCode::factory()->assigned($kid)->create();

        $this->assertTrue($qrCode->isAssigned());
        $this->assertEquals($kid->id, $qrCode->kid_id);

        $qrCode->markAsLost();
        $qrCode->refresh();

        $this->assertTrue($qrCode->isLost());
        $this->assertNull($qrCode->kid_id);
        $this->assertNull($qrCode->assigned_at);
    }

    #[Test]
    public function it_can_unassign_qr_code(): void
    {
        $kid = Kid::factory()->create();
        $qrCode = QrCode::factory()->assigned($kid)->create();

        $this->assertTrue($qrCode->isAssigned());

        $qrCode->unassign();
        $qrCode->refresh();

        $this->assertTrue($qrCode->isAvailable());
        $this->assertNull($qrCode->kid_id);
        $this->assertNull($qrCode->assigned_at);
    }

    #[Test]
    public function kid_can_have_assigned_qr_code(): void
    {
        $kid = Kid::factory()->create();
        $qrCode = QrCode::factory()->assigned($kid)->create();

        $this->assertNotNull($kid->qrCode);
        $this->assertEquals($qrCode->id, $kid->qrCode->id);
    }

    #[Test]
    public function kid_loses_qr_code_when_marked_as_lost(): void
    {
        $kid = Kid::factory()->create();
        $qrCode = QrCode::factory()->assigned($kid)->create();

        $this->assertNotNull($kid->fresh()->qrCode);

        $qrCode->markAsLost();

        $this->assertNull($kid->fresh()->qrCode);
    }

    #[Test]
    public function kid_can_be_assigned_new_qr_after_losing_old_one(): void
    {
        $kid = Kid::factory()->create();
        $oldQrCode = QrCode::factory()->assigned($kid)->create();
        $newQrCode = QrCode::factory()->create();

        $oldQrCode->markAsLost();

        $this->assertNull($kid->fresh()->qrCode);

        $newQrCode->assignToKid($kid);

        $this->assertNotNull($kid->fresh()->qrCode);
        $this->assertEquals($newQrCode->id, $kid->fresh()->qrCode->id);
    }

    #[Test]
    public function qr_code_status_enum_has_correct_labels(): void
    {
        $this->assertEquals('Disponible', QrCodeStatus::Available->getLabel());
        $this->assertEquals('Asignado', QrCodeStatus::Assigned->getLabel());
        $this->assertEquals('Perdido', QrCodeStatus::Lost->getLabel());
    }

    #[Test]
    public function qr_code_status_enum_has_correct_colors(): void
    {
        $this->assertEquals('success', QrCodeStatus::Available->getColor());
        $this->assertEquals('info', QrCodeStatus::Assigned->getColor());
        $this->assertEquals('danger', QrCodeStatus::Lost->getColor());
    }

    #[Test]
    public function it_can_print_single_qr_code(): void
    {
        $qrCode = QrCode::factory()->create();

        $response = $this->get(route('qr-codes.print', $qrCode));

        $response->assertStatus(200);
        $response->assertSee($qrCode->code);
    }

    #[Test]
    public function it_can_print_batch_of_qr_codes(): void
    {
        $qrCodes = QrCode::factory()->count(3)->create();
        $ids = $qrCodes->pluck('id')->join(',');

        $response = $this->get(route('qr-codes.print-batch', ['ids' => $ids]));

        $response->assertStatus(200);
        foreach ($qrCodes as $qrCode) {
            $response->assertSee($qrCode->code);
        }
    }

    #[Test]
    public function qr_code_requires_unique_code(): void
    {
        QrCode::factory()->create(['code' => 'LRK-0001']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        QrCode::factory()->create(['code' => 'LRK-0001']);
    }

    #[Test]
    public function qr_code_belongs_to_kid(): void
    {
        $kid = Kid::factory()->create();
        $qrCode = QrCode::factory()->assigned($kid)->create();

        $this->assertInstanceOf(Kid::class, $qrCode->kid);
        $this->assertEquals($kid->id, $qrCode->kid->id);
    }
}
