<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El seeder no corre en producción, así que la plantilla de cumpleaños se
     * crea aquí. Si alguien ya la creó a mano desde el panel, se respeta.
     */
    public function up(): void
    {
        $exists = DB::table('tutor_messages')->where('label', 'birthday')->exists();

        if ($exists) {
            return;
        }

        DB::table('tutor_messages')->insert([
            'label' => 'birthday',
            'name' => 'Cumpleaños',
            'message' => '¡Hola [tutor]! 🎉 En Piedritas queremos desearle un muy feliz cumpleaños a [nino]. Damos gracias a Dios por su vida y le pedimos que le siga llenando de alegría, salud y amor. ¡Lo celebramos contigo!',
            'description' => 'Se envía al registrar la entrada de un niño cuyo cumpleaños fue en los últimos 7 días.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tutor_messages')->where('label', 'birthday')->delete();
    }
};
