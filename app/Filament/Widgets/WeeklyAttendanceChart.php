<?php

namespace App\Filament\Widgets;

use App\Enums\ServiceTime;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class WeeklyAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Asistencia del Mes Actual';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected static bool $isDiscovered = false;

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
        $firstData = [];
        $secondData = [];

        foreach ($sundays as $sunday) {
            $labels[] = $sunday->format('d M');

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
