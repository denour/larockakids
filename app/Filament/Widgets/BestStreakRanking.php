<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Kid;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class BestStreakRanking extends BaseWidget
{
    protected static ?int $sort = 3;

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
                    ->state(function ($record, $rowLoop) {
                        return $rowLoop->iteration;
                    })
                    ->alignCenter()
                    ->width('50px'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Niño'),
                Tables\Columns\TextColumn::make('streak')
                    ->label('Racha')
                    ->state(function ($record) {
                        $streakData = $this->calculateStreaks();
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
        $sundays = $this->getSundaysFromPast(52);
        $kids = Kid::whereNotNull('birth_date')->get();
        $streaks = [];

        foreach ($kids as $kid) {
            $attendanceDates = Attendance::where('kid_id', $kid->id)
                ->pluck('check_in')
                ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                ->toArray();

            $streak = 0;
            foreach ($sundays as $sunday) {
                $sundayDate = $sunday->format('Y-m-d');
                if (in_array($sundayDate, $attendanceDates)) {
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
