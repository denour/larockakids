<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Kid;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class RecentAbsencesRanking extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Ausencias Recientes';

    public function table(Table $table): Table
    {
        $absenceData = $this->calculateAbsences();

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
                    ->label('Niño'),
                Tables\Columns\TextColumn::make('last_attendance')
                    ->label('Última Asistencia')
                    ->state(function ($record) {
                        $absenceData = $this->calculateAbsences();
                        $kidAbsence = $absenceData->firstWhere('kid_id', $record->id);

                        return $kidAbsence ? $kidAbsence['last_attendance'] : '-';
                    })
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('sundays_missed')
                    ->label('Domingos Sin Venir')
                    ->state(function ($record) {
                        $absenceData = $this->calculateAbsences();
                        $kidAbsence = $absenceData->firstWhere('kid_id', $record->id);

                        return $kidAbsence ? $kidAbsence['sundays_missed'] : 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated(false);
    }

    /**
     * Calculate absences for kids who have attended before but have been missing.
     */
    protected function calculateAbsences(): Collection
    {
        $lastSunday = Carbon::now();
        if (! $lastSunday->isSunday()) {
            $lastSunday = $lastSunday->previous(Carbon::SUNDAY);
        }

        $kids = Kid::whereNotNull('birth_date')
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

            if ($sundaysMissed > 0) {
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
