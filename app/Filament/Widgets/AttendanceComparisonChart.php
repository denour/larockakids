<?php

namespace App\Filament\Widgets;

use App\Enums\ServiceTime;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Comparativa: Este Mes vs Mes Anterior';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 2;

    protected static ?string $pollingInterval = null;

    protected static bool $isDiscovered = false;

    protected function getData(): array
    {
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $thisMonthSundays = $this->getSundaysInRange(Carbon::now()->startOfMonth(), Carbon::now());
        $lastMonthSundays = $this->getSundaysInRange($lastMonthStart, $lastMonthEnd);

        $thisFirst = 0;
        $thisSecond = 0;
        foreach ($thisMonthSundays as $sunday) {
            $thisFirst += Attendance::whereDate('check_in', $sunday)->where('service', ServiceTime::First)->count();
            $thisSecond += Attendance::whereDate('check_in', $sunday)->where('service', ServiceTime::Second)->count();
        }

        $lastFirst = 0;
        $lastSecond = 0;
        foreach ($lastMonthSundays as $sunday) {
            $lastFirst += Attendance::whereDate('check_in', $sunday)->where('service', ServiceTime::First)->count();
            $lastSecond += Attendance::whereDate('check_in', $sunday)->where('service', ServiceTime::Second)->count();
        }

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return [
            'datasets' => [
                [
                    'label' => '1ra Reunión (11 AM)',
                    'data' => [$lastFirst, $thisFirst],
                    'backgroundColor' => ['#36A2EB', '#36A2EB'],
                ],
                [
                    'label' => '2da Reunión (1 PM)',
                    'data' => [$lastSecond, $thisSecond],
                    'backgroundColor' => ['#FF9F40', '#FF9F40'],
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
