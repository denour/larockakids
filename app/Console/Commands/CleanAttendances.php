<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanAttendances extends Command
{
    protected $signature = 'attendances:clean';
    protected $description = 'Limpia las asistencias del día que ya tienen registrada su hora de salida';

    public function handle()
    {
        $today = Carbon::today();
        
        $count = Attendance::whereDate('check_in', $today)
            ->whereNotNull('check_out')
            ->delete();

        $this->info("Se han archivado {$count} registros de asistencia del día {$today->format('d/m/Y')}");
    }
} 