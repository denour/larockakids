<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class WeeklyAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Asistencia del Mes Actual';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $sundays = [];
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            if ($current->isSunday()) {
                $sundays[] = $current->copy();
            }
            $current->addDay();
        }

        $labels = [];
        $data = [];

        foreach ($sundays as $sunday) {
            $labels[] = $sunday->format('d M');

            $count = Attendance::whereDate('check_in', $sunday)->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Asistencias',
                    'data' => $data,
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
