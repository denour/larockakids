<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $totalAttendances = Attendance::whereDate('check_in', $today)->count();
        $checkedOut = Attendance::whereDate('check_in', $today)
            ->whereNotNull('check_out')
            ->count();
        $stillPresent = $totalAttendances - $checkedOut;

        return [
            Stat::make('Total Asistencias', $totalAttendances)
                ->description('Hoy')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Presentes', $stillPresent)
                ->description('En el centro')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Salidas', $checkedOut)
                ->description('Registradas')
                ->descriptionIcon('heroicon-m-arrow-right')
                ->color('info'),
        ];
    }
} 