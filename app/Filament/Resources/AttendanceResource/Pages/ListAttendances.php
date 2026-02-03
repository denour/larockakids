<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Filament\Widgets\AttendanceStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('history')
                ->label('Ver Historial')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(AttendanceResource::getUrl('history')),
            Actions\CreateAction::make()
                ->label('Registrar Asistencia'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceStats::class,
        ];
    }
}
