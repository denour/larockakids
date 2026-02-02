<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AgeDistributionChart;
use App\Filament\Widgets\AttendanceComparisonChart;
use App\Filament\Widgets\AttendanceHistoryStats;
use App\Filament\Widgets\GenderDistributionChart;
use App\Filament\Widgets\QuarterlyAttendanceChart;
use App\Filament\Widgets\WeeklyAttendanceChart;
use App\Filament\Widgets\YearlyAttendanceChart;
use Filament\Pages\Page;

class Statistics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Estadísticas';

    protected static ?string $title = 'Estadísticas';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.statistics';

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceHistoryStats::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AgeDistributionChart::class,
            GenderDistributionChart::class,
            WeeklyAttendanceChart::class,
            AttendanceComparisonChart::class,
            QuarterlyAttendanceChart::class,
            YearlyAttendanceChart::class,
        ];
    }
}
