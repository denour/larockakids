<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Illuminate\Console\Command;

class CleanAttendances extends Command
{
    protected $signature = 'attendances:clean';

    protected $description = 'Marca como RETIRADO toda asistencia que ya tiene hora de salida';

    /**
     * No se limita al día de hoy: el cron no corría (ver routes/console.php) y dejó asistencias
     * viejas en "En Clase" aunque ya tenían salida. Así una sola corrida las repara.
     */
    public function handle(): int
    {
        $count = Attendance::query()
            ->whereNotNull('check_out')
            ->where('status', '!=', AttendanceStatus::RETIRADO)
            ->update(['status' => AttendanceStatus::RETIRADO]);

        $this->info("Se marcaron {$count} asistencias como RETIRADO.");

        return self::SUCCESS;
    }
}
