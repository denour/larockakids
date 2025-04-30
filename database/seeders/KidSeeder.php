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
        // Crear 20 niños, aproximadamente 6 tendrán cumpleaños este mes (30%)
        Kid::factory()->count(20)->create();
    }
}
