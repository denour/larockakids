<?php

namespace App\Filament\Widgets;

use App\Models\Kid;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BestStreakRanking extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Mejor Racha';

    public function table(Table $table): Table
    {
        $streakData = $this->calculateStreaks();

        return $table
            ->query(
                Kid::query()
                    ->whereNotNull('birth_date')
                    ->whereIn('id', $streakData->pluck('kid_id'))
            )
            ->modifyQueryUsing(function ($query) use ($streakData) {
                $kidIds = $streakData->pluck('kid_id')->toArray();
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
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->state(fn ($record, $rowLoop) => $rowLoop->iteration)
                    ->alignCenter()
                    ->width('50px'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Niño'),
                Tables\Columns\TextColumn::make('streak')
                    ->label('Racha')
                    ->state(function ($record) use ($streakData) {
                        $kidStreak = $streakData->firstWhere('kid_id', $record->id);

                        return $kidStreak ? $kidStreak['streak'].' dom.' : '0 dom.';
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),
            ])
            ->paginated(false);
    }

    /**
     * Calculate consecutive Sunday streaks for all kids.
     */
    protected function calculateStreaks(): Collection
    {
        return Cache::remember('best_streaks', now()->addHours(1), function () {
            $sundays = $this->getSundaysFromPast(52);

            // Load all kids with their attendances in one query
            $kids = Kid::whereNotNull('birth_date')
                ->whereHas('attendances')
                ->with(['attendances:id,kid_id,check_in'])
                ->get();

            $streaks = [];

            foreach ($kids as $kid) {
                $attendanceDates = $kid->attendances
                    ->pluck('check_in')
                    ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                    ->toArray();

                $streak = 0;
                foreach ($sundays as $sunday) {
                    if (in_array($sunday->format('Y-m-d'), $attendanceDates)) {
                        $streak++;
                    } else {
                        break;
                    }
                }

                if ($streak > 0) {
                    $streaks[] = [
                        'kid_id' => $kid->id,
                        'streak' => $streak,
                    ];
                }
            }

            return collect($streaks)
                ->sortByDesc('streak')
                ->take(10)
                ->values();
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
