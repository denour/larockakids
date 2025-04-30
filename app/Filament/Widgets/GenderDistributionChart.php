<?php

namespace App\Filament\Widgets;

use App\Models\Kid;
use Filament\Widgets\ChartWidget;

class GenderDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Distribución por Género';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    protected int | string | array $columnStart = 2;

    protected function getData(): array
    {
        $kids = Kid::all();
        $genderCount = [
            'Niños' => 0,
            'Niñas' => 0
        ];

        foreach ($kids as $kid) {
            if ($kid->gender === 'male') {
                $genderCount['Niños']++;
            } else {
                $genderCount['Niñas']++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Distribución por Género',
                    'data' => array_values($genderCount),
                    'backgroundColor' => [
                        '#36A2EB',
                        '#FF6384'
                    ],
                ],
            ],
            'labels' => array_keys($genderCount),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
} 