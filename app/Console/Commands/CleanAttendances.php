<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanAttendances extends Command
{
    protected $signature = 'attendances:clean';
    protected $description = 'Actualiza el estado de las asistencias del día que ya tienen registrada su hora de salida';

    public function handle()
    {
        $today = Carbon::today();
        
        $count = Attendance::whereDate('check_in', $today)
            ->whereNotNull('check_out')
            ->where('status', '!=', AttendanceStatus::RETIRADO)
            ->update(['status' => AttendanceStatus::RETIRADO]);

        $this->info("Se han actualizado {$count} registros de asistencia del día {$today->format('d/m/Y')} a estado RETIRADO");
    }
} 