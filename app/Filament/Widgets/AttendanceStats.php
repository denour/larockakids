<?php

namespace App\Filament\Widgets;

use App\Enums\ServiceTime;
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

        $firstService = Attendance::whereDate('check_in', $today)->where('service', ServiceTime::First)->count();
        $secondService = Attendance::whereDate('check_in', $today)->where('service', ServiceTime::Second)->count();
        $totalAttendances = $firstService + $secondService;

        $stillPresent = Attendance::whereDate('check_in', $today)
            ->whereNull('check_out')
            ->count();

        $checkedOut = $totalAttendances - $stillPresent;

        return [
            Stat::make('Total Asistencias', $totalAttendances)
                ->description('Hoy')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('1ra Reunión (11 AM)', $firstService)
                ->description('Hoy')
                ->descriptionIcon('heroicon-m-sun')
                ->color('info'),

            Stat::make('2da Reunión (1 PM)', $secondService)
                ->description('Hoy')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Presentes', $stillPresent)
                ->description('En el centro')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
