<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Kid;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class YoungAbsencesRanking extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Inasistencias (-5 años)';

    public function table(Table $table): Table
    {
        $absenceData = $this->calculateYoungAbsences();

        return $table
            ->query(
                Kid::query()
                    ->whereNotNull('birth_date')
                    ->whereIn('id', $absenceData->pluck('kid_id'))
            )
            ->modifyQueryUsing(function ($query) use ($absenceData) {
                $kidIds = $absenceData->pluck('kid_id')->toArray();
                if (empty($kidIds)) {
                    return $query;
                }

                $orderCase = 'CASE id ';
                foreach ($kidIds as $index => $kidId) {
                    $orderCase .= "WHEN {$kidId} THEN {$index} ";
                }
                $orderCase .= 'END';

                return $query->orderByRaw($orderCase);
            })
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Niño')
                    ->description(fn ($record) => $record->age.' años'),
                Tables\Columns\TextColumn::make('last_attendance')
                    ->label('Última Asistencia')
                    ->state(function ($record) use ($absenceData) {
                        $kidAbsence = $absenceData->firstWhere('kid_id', $record->id);

                        return $kidAbsence ? $kidAbsence['last_attendance'] : '-';
                    })
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('sundays_missed')
                    ->label('Faltas')
                    ->state(function ($record) use ($absenceData) {
                        $kidAbsence = $absenceData->firstWhere('kid_id', $record->id);

                        return $kidAbsence ? $kidAbsence['sundays_missed'] : 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin inasistencias')
            ->emptyStateDescription('No hay niños menores de 5 años con 3+ domingos sin asistir');
    }

    /**
     * Calculate absences for kids under 5 years old who missed at least 3 Sundays.
     */
    protected function calculateYoungAbsences(): Collection
    {
        $lastSunday = Carbon::now();
        if (! $lastSunday->isSunday()) {
            $lastSunday = $lastSunday->previous(Carbon::SUNDAY);
        }

        // Get kids under 5 years old who have attended before
        $fiveYearsAgo = Carbon::now()->subYears(5);
        $kids = Kid::whereNotNull('birth_date')
            ->where('birth_date', '>', $fiveYearsAgo)
            ->whereHas('attendances')
            ->get();

        $absences = [];

        foreach ($kids as $kid) {
            $lastAttendance = Attendance::where('kid_id', $kid->id)
                ->orderByDesc('check_in')
                ->first();

            if (! $lastAttendance) {
                continue;
            }

            $lastAttendanceDate = Carbon::parse($lastAttendance->check_in);
            $sundaysMissed = $this->countSundaysBetween($lastAttendanceDate, $lastSunday);

            // Only show kids who missed at least 3 Sundays
            if ($sundaysMissed >= 3) {
                $absences[] = [
                    'kid_id' => $kid->id,
                    'last_attendance' => $lastAttendanceDate->format('d M Y'),
                    'sundays_missed' => $sundaysMissed,
                ];
            }
        }

        return collect($absences)
            ->sortByDesc('sundays_missed')
            ->take(10)
            ->values();
    }

    /**
     * Count the number of Sundays between two dates (exclusive of start date).
     */
    protected function countSundaysBetween(Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;
        $current = $startDate->copy();

        if (! $current->isSunday()) {
            $current = $current->next(Carbon::SUNDAY);
        } else {
            $current = $current->addWeek();
        }

        while ($current->lte($endDate)) {
            $count++;
            $current->addWeek();
        }

        return $count;
    }
}
