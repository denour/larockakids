<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Kid;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TopAttendanceRanking extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top 10 Asistencias';

    public function table(Table $table): Table
    {
        $undefeatedKids = $this->getUndefeatedKids();

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
                    ->state(fn ($record, $rowLoop) => $rowLoop->iteration)
                    ->alignCenter()
                    ->width('50px'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Niño')
                    ->description(fn ($record) => $undefeatedKids->contains($record->id) ? '🏆 Invicto' : null),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('Total')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => $undefeatedKids->contains($record->id) ? 'warning' : 'success'),
            ])
            ->paginated(false);
    }

    /**
     * Get list of kid IDs who have never missed a Sunday since their first attendance.
     */
    protected function getUndefeatedKids(): Collection
    {
        return Cache::remember('undefeated_kids', now()->addHours(1), function () {
            $sundays = $this->getSundaysFromPast(52);

            // Load all kids with their attendances in one query
            $kids = Kid::whereNotNull('birth_date')
                ->whereHas('attendances')
                ->with(['attendances:id,kid_id,check_in'])
                ->get();

            $undefeated = collect();

            foreach ($kids as $kid) {
                $attendanceDates = $kid->attendances
                    ->pluck('check_in')
                    ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                    ->toArray();

                if (empty($attendanceDates)) {
                    continue;
                }

                $firstDate = Carbon::parse(min($attendanceDates));

                $isUndefeated = true;
                foreach ($sundays as $sunday) {
                    if ($sunday->lt($firstDate->startOfDay())) {
                        continue;
                    }

                    if (! in_array($sunday->format('Y-m-d'), $attendanceDates)) {
                        $isUndefeated = false;
                        break;
                    }
                }

                if ($isUndefeated) {
                    $undefeated->push($kid->id);
                }
            }

            return $undefeated;
        });
    }

    /**
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
