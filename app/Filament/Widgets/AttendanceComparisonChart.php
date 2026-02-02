<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Comparativa: Este Mes vs Mes Anterior';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 2;

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $thisMonthSundays = $this->getSundaysInRange($thisMonthStart, Carbon::now());
        $lastMonthSundays = $this->getSundaysInRange($lastMonthStart, $lastMonthEnd);

        $thisMonthTotal = 0;
        foreach ($thisMonthSundays as $sunday) {
            $thisMonthTotal += Attendance::whereDate('check_in', $sunday)->count();
        }

        $lastMonthTotal = 0;
        foreach ($lastMonthSundays as $sunday) {
            $lastMonthTotal += Attendance::whereDate('check_in', $sunday)->count();
        }

        $thisMonthAvg = count($thisMonthSundays) > 0
            ? round($thisMonthTotal / count($thisMonthSundays))
            : 0;

        $lastMonthAvg = count($lastMonthSundays) > 0
            ? round($lastMonthTotal / count($lastMonthSundays))
            : 0;

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total Asistencias',
                    'data' => [$lastMonthTotal, $thisMonthTotal],
                    'backgroundColor' => ['#FF6384', '#36A2EB'],
                ],
                [
                    'label' => 'Promedio por Domingo',
                    'data' => [$lastMonthAvg, $thisMonthAvg],
                    'backgroundColor' => ['#FF9F40', '#4BC0C0'],
                ],
            ],
            'labels' => [
                $monthNames[Carbon::now()->subMonth()->month],
                $monthNames[Carbon::now()->month],
            ],
        ];
    }

    /**
     * @return array<Carbon>
     */
    private function getSundaysInRange(Carbon $start, Carbon $end): array
    {
        $sundays = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isSunday()) {
                $sundays[] = $current->copy();
            }
            $current->addDay();
        }

        return $sundays;
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
