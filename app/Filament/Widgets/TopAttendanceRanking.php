<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Kid;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class TopAttendanceRanking extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top 10 Asistencias';

    protected ?Collection $undefeatedKids = null;

    public function table(Table $table): Table
    {
        $this->undefeatedKids = $this->getUndefeatedKids();

        return $table
            ->query(
                Kid::query()
                    ->whereNotNull('birth_date')
                    ->whereHas('attendances')
                    ->withCount('attendances')
                    ->orderByDesc('attendances_count')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->state(function ($record, $rowLoop) {
                        return $rowLoop->iteration;
                    })
                    ->alignCenter()
                    ->width('50px'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Niño')
                    ->description(function ($record) {
                        if ($this->undefeatedKids->contains($record->id)) {
                            return '🏆 Invicto';
                        }

                        return null;
                    })
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('Total')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => $this->undefeatedKids->contains($record->id) ? 'warning' : 'success'),
            ])
            ->paginated(false);
    }

    /**
     * Get list of kid IDs who have never missed a Sunday since their first attendance.
     */
    protected function getUndefeatedKids(): Collection
    {
        $sundays = $this->getSundaysFromPast(52);
        $kids = Kid::whereNotNull('birth_date')
            ->whereHas('attendances')
            ->get();

        $undefeated = collect();

        foreach ($kids as $kid) {
            $attendanceDates = Attendance::where('kid_id', $kid->id)
                ->pluck('check_in')
                ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                ->toArray();

            if (empty($attendanceDates)) {
                continue;
            }

            // Find the first Sunday the kid attended
            $firstAttendanceDate = Attendance::where('kid_id', $kid->id)
                ->orderBy('check_in')
                ->first();

            if (! $firstAttendanceDate) {
                continue;
            }

            $firstDate = Carbon::parse($firstAttendanceDate->check_in);

            // Check all Sundays from first attendance to now
            $isUndefeated = true;
            foreach ($sundays as $sunday) {
                // Only check Sundays after first attendance
                if ($sunday->lt($firstDate->startOfDay())) {
                    continue;
                }

                $sundayDate = $sunday->format('Y-m-d');
                if (! in_array($sundayDate, $attendanceDates)) {
                    $isUndefeated = false;
                    break;
                }
            }

            if ($isUndefeated) {
                $undefeated->push($kid->id);
            }
        }

        return $undefeated;
    }

    /**
     * Get array of Sundays from today going back.
     *
     * @return array<Carbon>
     */
    protected function getSundaysFromPast(int $weeks): array
    {
        $sundays = [];
        $current = Carbon::now();

        if (! $current->isSunday()) {
            $current = $current->previous(Carbon::SUNDAY);
        }

        for ($i = 0; $i < $weeks; $i++) {
            $sundays[] = $current->copy();
            $current->subWeek();
        }

        return $sundays;
    }
}
