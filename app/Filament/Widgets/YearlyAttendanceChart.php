<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class YearlyAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Asistencia Mensual (Último Año)';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subYear()->startOfMonth();

        $monthlyData = Attendance::query()
            ->whereBetween('check_in', [$startDate, $endDate])
            ->selectRaw('YEAR(check_in) as year, MONTH(check_in) as month, COUNT(*) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $labels = [];
        $data = [];
        $current = $startDate->copy();

        $monthNames = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        while ($current->lte($endDate)) {
            $key = $current->format('Y-m');
            $labels[] = $monthNames[$current->month].' '.$current->format('y');
            $data[] = $monthlyData->get($key)?->total ?? 0;
            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Asistencias',
                    'data' => $data,
                    'backgroundColor' => '#9966FF',
                    'borderColor' => '#9966FF',
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
