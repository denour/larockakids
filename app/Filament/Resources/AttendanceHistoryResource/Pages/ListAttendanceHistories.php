<?php

namespace App\Filament\Resources\AttendanceHistoryResource\Pages;

use App\Filament\Resources\AttendanceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceHistories extends ListRecords
{
    protected static string $resource = AttendanceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
} 