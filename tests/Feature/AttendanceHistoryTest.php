<?php

namespace Tests\Feature;

use App\Filament\Resources\AttendanceResource\Pages\AttendanceHistory;
use App\Filament\Widgets\AttendanceHistoryStats;
use App\Filament\Widgets\BestStreakRanking;
use App\Filament\Widgets\RecentAbsencesRanking;
use App\Filament\Widgets\TopAttendanceRanking;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Kid $kid;

    private Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->contact = Contact::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'phone' => '5551234567',
            'international_code' => '+52',
        ]);

        $this->kid = Kid::create([
            'first_name' => 'María',
            'last_name' => 'Pérez',
            'birth_date' => now()->subYears(5),
            'gender' => 'female',
        ]);

        $this->kid->contacts()->attach($this->contact->id, ['relationship_type' => 'Padre/Madre']);
    }

    public function test_history_page_loads(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/admin/attendances/history');

        $response->assertSuccessful();
    }

    public function test_history_shows_all_attendances_including_checked_out(): void
    {
        $this->actingAs($this->user);

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => Carbon::today()->subDays(7),
            'check_out' => Carbon::today()->subDays(7)->addHours(2),
        ]);

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => Carbon::today(),
            'check_out' => null,
        ]);

        Livewire::test(AttendanceHistory::class)
            ->assertCanSeeTableRecords(Attendance::all());
    }

    public function test_history_stats_widget_renders(): void
    {
        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => Carbon::today(),
        ]);

        Livewire::test(AttendanceHistoryStats::class)
            ->assertSee('Este Mes')
            ->assertSee('Mes Pasado')
            ->assertSee('Total General')
            ->assertSee('Promedio por Domingo');
    }

    public function test_top_attendance_ranking_shows_correct_order(): void
    {
        $kid2 = Kid::create([
            'first_name' => 'Carlos',
            'last_name' => 'López',
            'birth_date' => now()->subYears(4),
            'gender' => 'male',
        ]);
        $kid2->contacts()->attach($this->contact->id, ['relationship_type' => 'Tío']);

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => Carbon::today(),
        ]);

        Attendance::create([
            'kid_id' => $kid2->id,
            'contact_id' => $this->contact->id,
            'check_in' => Carbon::today(),
        ]);

        Attendance::create([
            'kid_id' => $kid2->id,
            'contact_id' => $this->contact->id,
            'check_in' => Carbon::today()->subWeek(),
        ]);

        Livewire::test(TopAttendanceRanking::class)
            ->assertSee('Carlos López')
            ->assertSee('María Pérez')
            ->assertSee('Top Asistencias');
    }

    public function test_streak_calculation_is_correct(): void
    {
        $lastSunday = Carbon::now();
        if (! $lastSunday->isSunday()) {
            $lastSunday = $lastSunday->previous(Carbon::SUNDAY);
        }

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => $lastSunday,
        ]);

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => $lastSunday->copy()->subWeek(),
        ]);

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => $lastSunday->copy()->subWeeks(2),
        ]);

        Livewire::test(BestStreakRanking::class)
            ->assertSee('María Pérez')
            ->assertSee('3 dom.')
            ->assertSee('Mejor Racha');
    }

    public function test_absences_count_is_correct(): void
    {
        $lastSunday = Carbon::now();
        if (! $lastSunday->isSunday()) {
            $lastSunday = $lastSunday->previous(Carbon::SUNDAY);
        }

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => $lastSunday->copy()->subWeeks(3),
        ]);

        Livewire::test(RecentAbsencesRanking::class)
            ->assertSee('María Pérez')
            ->assertSee('Ausencias Recientes');
    }

    public function test_history_stats_counts_correctly(): void
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => $startOfMonth->copy()->addDays(5),
        ]);

        Attendance::create([
            'kid_id' => $this->kid->id,
            'contact_id' => $this->contact->id,
            'check_in' => $startOfLastMonth->copy()->addDays(5),
        ]);

        Livewire::test(AttendanceHistoryStats::class)
            ->assertSee('1');
    }

    public function test_history_page_has_date_filter(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AttendanceHistory::class)
            ->assertTableFilterExists('date');
    }
}
