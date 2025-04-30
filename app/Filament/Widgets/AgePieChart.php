<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AgePieChart extends ChartWidget
{
    protected static ?string $heading = 'Distribución por Edad';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '200px';

    protected function getData(): array
    {
        $attendances = Attendance::whereDate('check_in', Carbon::today())
            ->with('kid')
            ->get();

        $ageData = $attendances->groupBy(function ($attendance) {
            $age = Carbon::parse($attendance->kid->birth_date)->age;
            if ($age < 1) return 'Menos de 1 año';
            if ($age < 2) return '1 año';
            if ($age < 3) return '2 años';
            if ($age < 4) return '3 años';
            if ($age < 5) return '4 años';
            return '5 años o más';
        })->map->count();

        return [
            'datasets' => [
                [
                    'label' => 'Distribución por Edad',
                    'data' => $ageData->values()->toArray(),
                    'backgroundColor' => [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $ageData->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 10,
                        'font' => [
                            'size' => 10
                        ]
                    ]
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
} 