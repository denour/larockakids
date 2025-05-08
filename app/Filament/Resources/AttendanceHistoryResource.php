<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceHistoryResource\Pages;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceHistoryResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Historial de Asistencias';

    protected static ?string $modelLabel = 'Historial de Asistencia';

    protected static ?string $pluralModelLabel = 'Historial de Asistencias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('check_in', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kid.full_name')
                    ->label('Niño')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kid.age')
                    ->label('Edad')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color(fn ($state) => $state->getColor())
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('week')
                    ->label('Semana')
                    ->options(function () {
                        $options = [];
                        $now = Carbon::now();
                        $startOfMonth = $now->copy()->startOfMonth();
                        $endOfMonth = $now->copy()->endOfMonth();

                        while ($startOfMonth->lte($endOfMonth)) {
                            if ($startOfMonth->isSunday()) {
                                $weekStart = $startOfMonth->copy();
                                $weekEnd = $startOfMonth->copy()->endOfWeek();
                                $options[$weekStart->format('Y-m-d')] = "Semana del {$weekStart->format('d/m')} al {$weekEnd->format('d/m')}";
                            }
                            $startOfMonth->addDay();
                        }

                        return $options;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        $weekStart = Carbon::parse($data['value']);
                        $weekEnd = $weekStart->copy()->endOfWeek();

                        return $query->whereBetween('check_in', [$weekStart, $weekEnd]);
                    })
                    ->default(Carbon::now()->startOfWeek()->format('Y-m-d')),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceHistories::route('/'),
        ];
    }
} 