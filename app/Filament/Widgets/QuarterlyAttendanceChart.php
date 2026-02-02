<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class QuarterlyAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Asistencia Últimos 3 Meses';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $endDate = Carbon::now()->endOfDay();
        $startDate = Carbon::now()->subMonths(3)->startOfDay();

        $sundays = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($current->isSunday()) {
                $sundays[] = $current->copy();
            }
            $current->addDay();
        }

        $labels = [];
        $data = [];

        foreach ($sundays as $sunday) {
            $labels[] = $sunday->format('d/m');

            $count = Attendance::whereDate('check_in', $sunday)->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Asistencias por Domingo',
                    'data' => $data,
                    'borderColor' => '#4BC0C0',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
