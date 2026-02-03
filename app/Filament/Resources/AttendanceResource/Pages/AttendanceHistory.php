<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Filament\Widgets\AttendanceHistoryStats;
use App\Filament\Widgets\BestStreakRanking;
use App\Filament\Widgets\RecentAbsencesRanking;
use App\Filament\Widgets\TopAttendanceRanking;
use App\Filament\Widgets\YoungAbsencesRanking;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceHistory extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected static ?string $title = 'Historial de Asistencias';

    protected static ?string $navigationLabel = 'Historial';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Asistencias de Hoy')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AttendanceResource::getUrl('index')),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Attendance::query()
            ->with(['kid', 'contact'])
            ->whereNotNull('kid_id')
            ->whereHas('kid', function ($query) {
                $query->whereNotNull('birth_date');
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('check_in', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kid.full_name')
                    ->label('Niño')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('kid', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('kid.age')
                    ->label('Edad')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Responsable')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('contact', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Entrada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Salida')
                    ->dateTime('H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('date')
                    ->label('Fecha')
                    ->options([
                        'today' => 'Hoy',
                        'yesterday' => 'Ayer',
                        'this_week' => 'Esta semana',
                        'last_week' => 'Semana pasada',
                        'this_month' => 'Este mes',
                        'last_month' => 'Mes pasado',
                    ])
                    ->query(function (Builder $query, $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return;
                        }

                        $now = now();
                        switch ($value) {
                            case 'today':
                                $query->whereDate('check_in', $now);
                                break;
                            case 'yesterday':
                                $query->whereDate('check_in', $now->copy()->subDay());
                                break;
                            case 'this_week':
                                $query->whereBetween('check_in', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                                break;
                            case 'last_week':
                                $start = $now->copy()->subWeek()->startOfWeek();
                                $end = $now->copy()->subWeek()->endOfWeek();
                                $query->whereBetween('check_in', [$start, $end]);
                                break;
                            case 'this_month':
                                $query->whereMonth('check_in', $now->month)
                                    ->whereYear('check_in', $now->year);
                                break;
                            case 'last_month':
                                $lastMonth = $now->copy()->subMonth();
                                $query->whereMonth('check_in', $lastMonth->month)
                                    ->whereYear('check_in', $lastMonth->year);
                                break;
                        }
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceHistoryStats::class,
            TopAttendanceRanking::class,
            BestStreakRanking::class,
            RecentAbsencesRanking::class,
            YoungAbsencesRanking::class,
        ];
    }
}
