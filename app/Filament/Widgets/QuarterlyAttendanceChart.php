<?php

namespace App\Filament\Widgets;

use App\Enums\ServiceTime;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class QuarterlyAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Asistencia Últimos 3 Meses';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected static bool $isDiscovered = false;

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
        $firstData = [];
        $secondData = [];

        foreach ($sundays as $sunday) {
            $labels[] = $sunday->format('d/m');

            $firstData[] = Attendance::whereDate('check_in', $sunday)
                ->where('service', ServiceTime::First)
                ->count();

            $secondData[] = Attendance::whereDate('check_in', $sunday)
                ->where('service', ServiceTime::Second)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => '1ra Reunión (11 AM)',
                    'data' => $firstData,
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => '2da Reunión (1 PM)',
                    'data' => $secondData,
                    'borderColor' => '#FF9F40',
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
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
