<?php

namespace App\Filament\Widgets;

use App\Enums\ServiceTime;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class YearlyAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Asistencia Mensual (Último Año)';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected static bool $isDiscovered = false;

    protected function getData(): array
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subYear()->startOfMonth();

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $yearExpr = 'EXTRACT(YEAR FROM check_in)::integer';
            $monthExpr = 'EXTRACT(MONTH FROM check_in)::integer';
        } elseif ($driver === 'sqlite') {
            $yearExpr = "cast(strftime('%Y', check_in) as integer)";
            $monthExpr = "cast(strftime('%m', check_in) as integer)";
        } else {
            $yearExpr = 'YEAR(check_in)';
            $monthExpr = 'MONTH(check_in)';
        }

        $monthlyData = Attendance::query()
            ->whereBetween('check_in', [$startDate, $endDate])
            ->selectRaw("{$yearExpr} as year, {$monthExpr} as month, service, COUNT(*) as total")
            ->groupByRaw("{$yearExpr}, {$monthExpr}, service")
            ->orderByRaw("{$yearExpr}, {$monthExpr}")
            ->get();

        $labels = [];
        $firstData = [];
        $secondData = [];
        $current = $startDate->copy();

        $monthNames = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        while ($current->lte($endDate)) {
            $year = $current->year;
            $month = $current->month;
            $labels[] = $monthNames[$month].' '.$current->format('y');

            $firstData[] = (int) ($monthlyData
                ->where('year', $year)
                ->where('month', $month)
                ->where('service', ServiceTime::First->value)
                ->value('total') ?? 0);

            $secondData[] = (int) ($monthlyData
                ->where('year', $year)
                ->where('month', $month)
                ->where('service', ServiceTime::Second->value)
                ->value('total') ?? 0);

            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => '1ra Reunión (11 AM)',
                    'data' => $firstData,
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                ],
                [
                    'label' => '2da Reunión (1 PM)',
                    'data' => $secondData,
                    'backgroundColor' => '#FF9F40',
                    'borderColor' => '#FF9F40',
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
