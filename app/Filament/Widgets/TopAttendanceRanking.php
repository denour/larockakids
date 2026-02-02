<?php

namespace App\Filament\Widgets;

use App\Models\Kid;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopAttendanceRanking extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top Asistencias';

    public function table(Table $table): Table
    {
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
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('Total')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
            ])
            ->paginated(false);
    }
}
