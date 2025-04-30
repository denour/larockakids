<?php

namespace App\Filament\Widgets;

use App\Models\Kid;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class BirthdaysThisMonth extends BaseWidget
{
    protected static ?string $heading = 'Cumpleaños del Mes';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 1;

    protected int | string | array $columnStart = 1;

    public function table(Table $table): Table
    {
        $currentMonth = Carbon::now()->month;
        $today = Carbon::now();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $nextWeek = $today->copy()->addWeek();
        $endOfNextWeek = $nextWeek->copy()->endOfWeek();
        
        $orderByDay = DB::connection()->getDriverName() === 'pgsql' 
            ? 'EXTRACT(DAY FROM birth_date)'
            : 'DAYOFMONTH(birth_date)';
        
        return $table
            ->query(
                Kid::query()
                    ->whereMonth('birth_date', $currentMonth)
                    ->orderByRaw($orderByDay)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre Completo')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha de Cumpleaños')
                    ->date('d/m')
                    ->sortable(),
                Tables\Columns\TextColumn::make('age')
                    ->label('Edad Actual')
                    ->formatStateUsing(fn (Kid $record) => $record->age . ' años')
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Edad que Cumple')
                    ->formatStateUsing(function ($state) {
                        $birthDate = Carbon::parse($state);
                        $age = $birthDate->age;
                        return ($age + 1) . ' años';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Cuándo')
                    ->formatStateUsing(function ($state) use ($today, $startOfWeek, $endOfWeek, $nextWeek, $endOfNextWeek) {
                        $birthDate = Carbon::parse($state);
                        $birthDateThisYear = $birthDate->copy()->year($today->year);
                        
                        if ($birthDateThisYear->isSameDay($today)) {
                            return '🎉 ¡Hoy!';
                        }
                        
                        if ($birthDateThisYear->between($startOfWeek, $endOfWeek)) {
                            return '📅 Esta semana';
                        }
                        
                        if ($birthDateThisYear->between($nextWeek, $endOfNextWeek)) {
                            return '⏳ En dos semanas';
                        }
                        
                        if ($birthDateThisYear->isBefore($startOfWeek)) {
                            return '✅ La semana pasada';
                        }
                        
                        return '📅 Próximamente';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('allergies.name')
                    ->label('Alergias')
                    ->listWithLineBreaks()
                    ->limitList(2),
            ])
            ->defaultSort('birth_date', 'asc');
    }
} 