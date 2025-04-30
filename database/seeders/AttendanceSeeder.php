<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todos los niños
        $kids = Kid::all();
        
        // Obtener todos los contactos
        $contacts = Contact::all();

        // Crear asistencias para hoy
        $today = Carbon::today();
        
        // Crear asistencias para cada niño
        foreach ($kids as $kid) {
            // 80% de probabilidad de asistencia
            if (rand(1, 100) <= 80) {
                // Seleccionar un contacto aleatorio para este niño
                $contact = $contacts->where('id', $kid->contacts()->inRandomOrder()->first()?->id)->first();
                
                if ($contact) {
                    Attendance::create([
                        'kid_id' => $kid->id,
                        'contact_id' => $contact->id,
                        'check_in' => $today->copy()->setHour(rand(7, 9))->setMinute(rand(0, 59)),
                        'check_out' => null,
                        'observations' => rand(1, 100) <= 20 ? 'Alergia a la leche' : null,
                    ]);
                }
            }
        }
    }
} 