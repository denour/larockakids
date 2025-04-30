<?php

namespace Database\Seeders;

use App\Models\Allergy;
use Illuminate\Database\Seeder;

class AllergySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allergies = [
            ['name' => 'Lácteos', 'color' => '#FFB6C1'], // Rosa claro
            ['name' => 'Gluten', 'color' => '#FFA07A'], // Salmón claro
            ['name' => 'Huevo', 'color' => '#FFD700'], // Oro
            ['name' => 'Frutos Secos', 'color' => '#8B4513'], // Marrón
            ['name' => 'Mariscos', 'color' => '#4169E1'], // Azul real
            ['name' => 'Soya', 'color' => '#32CD32'], // Verde lima
            ['name' => 'Pescado', 'color' => '#1E90FF'], // Azul dodger
            ['name' => 'Maní', 'color' => '#D2691E'], // Chocolate
            ['name' => 'Sésamo', 'color' => '#F4A460'], // Arena
            ['name' => 'Mostaza', 'color' => '#FFD700'], // Amarillo
            ['name' => 'Apio', 'color' => '#90EE90'], // Verde claro
            ['name' => 'Lupino', 'color' => '#9370DB'], // Púrpura medio
            ['name' => 'Moluscos', 'color' => '#87CEEB'], // Cielo
            ['name' => 'Kiwi', 'color' => '#9ACD32'], // Verde amarillo
            ['name' => 'Melocotón', 'color' => '#FFA07A'], // Salmón claro
            ['name' => 'Fresa', 'color' => '#FF69B4'], // Rosa caliente
            ['name' => 'Tomate', 'color' => '#FF6347'], // Tomate
            ['name' => 'Maíz', 'color' => '#FFD700'], // Amarillo
            ['name' => 'Trigo', 'color' => '#DEB887'], // Pan
            ['name' => 'Avena', 'color' => '#D2B48C'], // Bronceado
            ['name' => 'Cacahuete', 'color' => '#D2691E'], // Chocolate
            ['name' => 'Nueces', 'color' => '#8B4513'], // Marrón
            ['name' => 'Almendras', 'color' => '#DEB887'], // Pan
            ['name' => 'Avellanas', 'color' => '#D2B48C'], // Bronceado
            ['name' => 'Anacardos', 'color' => '#F4A460'], // Arena
            ['name' => 'Pistachos', 'color' => '#90EE90'], // Verde claro
            ['name' => 'Piñones', 'color' => '#D2691E'], // Chocolate
            ['name' => 'Castañas', 'color' => '#8B4513'], // Marrón
            ['name' => 'Nueces de Brasil', 'color' => '#DEB887'], // Pan
            ['name' => 'Macadamia', 'color' => '#D2B48C'], // Bronceado
        ];

        foreach ($allergies as $allergy) {
            Allergy::create($allergy);
        }
    }
} 