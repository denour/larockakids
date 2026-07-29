<?php

namespace Tests\Feature;

use App\Enums\ServiceTime;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\QrCode;
use App\Services\AttendanceScannerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_time_enum_returns_first_before_1pm(): void
    {
        $this->assertEquals(ServiceTime::First, ServiceTime::fromHour(0));
        $this->assertEquals(ServiceTime::First, ServiceTime::fromHour(10));
        $this->assertEquals(ServiceTime::First, ServiceTime::fromHour(11));
        $this->assertEquals(ServiceTime::First, ServiceTime::fromHour(12));
    }

    public function test_service_time_enum_returns_second_at_1pm_and_after(): void
    {
        $this->assertEquals(ServiceTime::Second, ServiceTime::fromHour(13));
        $this->assertEquals(ServiceTime::Second, ServiceTime::fromHour(14));
        $this->assertEquals(ServiceTime::Second, ServiceTime::fromHour(23));
    }

    public function test_check_in_before_1pm_assigns_first_service(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-03 11:30:00'));

        $kid = Kid::factory()->create(['birth_date' => now()->subYears(3)]);
        $contact = Contact::factory()->create();
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        $qrCode = QrCode::factory()->create(['kid_id' => $kid->id, 'status' => 'assigned']);

        $service = app(AttendanceScannerService::class);
        $result = $service->processCheckIn($qrCode->code);

        $this->assertTrue($result['success']);

        $attendance = Attendance::where('kid_id', $kid->id)->first();
        $this->assertEquals(ServiceTime::First, $attendance->service);

        Carbon::setTestNow();
    }

    public function test_check_in_at_1pm_assigns_second_service(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-03 13:00:00'));

        $kid = Kid::factory()->create(['birth_date' => now()->subYears(3)]);
        $contact = Contact::factory()->create();
        $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

        $qrCode = QrCode::factory()->create(['kid_id' => $kid->id, 'status' => 'assigned']);

        $service = app(AttendanceScannerService::class);
        $result = $service->processCheckIn($qrCode->code);

        $this->assertTrue($result['success']);

        $attendance = Attendance::where('kid_id', $kid->id)->first();
        $this->assertEquals(ServiceTime::Second, $attendance->service);

        Carbon::setTestNow();
    }

    public function test_attendance_model_casts_service_to_enum(): void
    {
        $kid = Kid::factory()->create();
        $contact = Contact::factory()->create();

        $attendance = Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'service' => ServiceTime::First,
            'status' => 'en_clase',
        ]);

        $attendance->refresh();

        $this->assertInstanceOf(ServiceTime::class, $attendance->service);
        $this->assertEquals(ServiceTime::First, $attendance->service);
    }

    public function test_service_time_labels(): void
    {
        $this->assertEquals('1ra Reunión (11 AM)', ServiceTime::First->getLabel());
        $this->assertEquals('2da Reunión (1 PM)', ServiceTime::Second->getLabel());
        $this->assertEquals('11 AM', ServiceTime::First->getShortLabel());
        $this->assertEquals('1 PM', ServiceTime::Second->getShortLabel());
    }
}
