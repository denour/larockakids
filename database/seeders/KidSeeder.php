<?php

namespace Database\Seeders;

use App\Models\Kid;
use Illuminate\Database\Seeder;

class KidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kid::factory()
            ->count(50)
            ->create([
                'birth_date' => function () {
                    // Generar fechas de nacimiento para niños menores de 5 años
                    $maxDate = now()->subYears(5);
                    $minDate = now()->subYears(1);
                    return fake()->dateTimeBetween($maxDate, $minDate)->format('Y-m-d');
                },
            ]);
    }
}
