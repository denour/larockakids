<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceHistoryStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now = Carbon::now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $endOfThisMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $thisMonthCount = Attendance::whereBetween('check_in', [$startOfThisMonth, $endOfThisMonth])->count();
        $lastMonthCount = Attendance::whereBetween('check_in', [$startOfLastMonth, $endOfLastMonth])->count();
        $totalCount = Attendance::count();

        $sundaysWithAttendance = Attendance::selectRaw('DATE(check_in) as attendance_date')
            ->groupBy('attendance_date')
            ->get()
            ->count();

        $averagePerSunday = $sundaysWithAttendance > 0
            ? round($totalCount / $sundaysWithAttendance, 1)
            : 0;

        return [
            Stat::make('Este Mes', $thisMonthCount)
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Mes Pasado', $lastMonthCount)
                ->description($now->copy()->subMonth()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Total General', $totalCount)
                ->description('Todas las asistencias')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('Promedio por Domingo', $averagePerSunday)
                ->description('Niños por servicio')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}
