<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class GenderPieChart extends ChartWidget
{
    protected static ?string $heading = 'Distribución por Género';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '200px';

    protected function getData(): array
    {
        $attendances = Attendance::whereDate('check_in', Carbon::today())
            ->with('kid')
            ->get();

        $genderData = $attendances->groupBy(function ($attendance) {
            return $attendance->kid->gender === 'male' ? 'Niños' : 'Niñas';
        })->map->count();

        return [
            'datasets' => [
                [
                    'label' => 'Distribución por Género',
                    'data' => $genderData->values()->toArray(),
                    'backgroundColor' => [
                        '#36A2EB', // Azul para niños
                        '#FF6384', // Rosa para niñas
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $genderData->keys()->toArray(),
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