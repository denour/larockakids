<?php

namespace App\Filament\Widgets;

use App\Models\Kid;
use Filament\Widgets\ChartWidget;

class AgeDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Edades';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    protected int | string | array $columnStart = 2;

    protected function getData(): array
    {
        $kids = Kid::all();
        $ageGroups = [
            '0-1' => 0,
            '2-3' => 0,
            '4-5' => 0,
            '6-7' => 0,
            '8+' => 0
        ];

        foreach ($kids as $kid) {
            $age = $kid->age;
            if ($age <= 1) {
                $ageGroups['0-1']++;
            } elseif ($age <= 3) {
                $ageGroups['2-3']++;
            } elseif ($age <= 5) {
                $ageGroups['4-5']++;
            } elseif ($age <= 7) {
                $ageGroups['6-7']++;
            } else {
                $ageGroups['8+']++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Niños por Edad',
                    'data' => array_values($ageGroups),
                    'backgroundColor' => [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ],
                ],
            ],
            'labels' => array_keys($ageGroups),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
} 