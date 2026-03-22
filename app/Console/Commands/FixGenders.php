<?php

namespace App\Console\Commands;

use App\Models\Kid;
use Illuminate\Console\Command;

class FixGenders extends Command
{
    protected $signature = 'kids:fix-genders';
    protected $description = 'Fix incorrectly assigned genders';

    public function handle()
    {
        $fixes = [
            // female → male
            107 => 'male',   // Ian Malo
            129 => 'male',   // Nicolás Valenzuela Nava
            // male → female
            108 => 'female', // Barbara Sarmiento Contreras
            110 => 'female', // Ivanna Saaram Alvarado Palacios
            138 => 'female', // Irlanda Concha Bedolla
            201 => 'female', // Alexia Jireh Martínez Palacios
            202 => 'female', // Amelie Gonzalez Toscado
            203 => 'female', // Stefany Amaia Gonzalez Torres
            209 => 'female', // jimena legorreta
            216 => 'female', // alexia 11 Jireh
            218 => 'female', // samara 6 Maldonado
            232 => 'female', // Paola Camila Castro Juarez
            254 => 'female', // Vanesa isabel Lopez Lara
            280 => 'female', // Emily Correa
            294 => 'female', // Luna Evangeline
            297 => 'female', // Valeria sinai
            311 => 'female', // abby campa
        ];

        $count = 0;
        foreach ($fixes as $id => $gender) {
            $kid = Kid::find($id);
            if ($kid) {
                $old = $kid->gender;
                $kid->update(['gender' => $gender]);
                $this->info("✓ [{$id}] {$kid->full_name}: {$old} → {$gender}");
                $count++;
            } else {
                $this->warn("⚠ Kid ID {$id} not found");
            }
        }

        $this->info("\nTotal corregidos: {$count}");
        return Command::SUCCESS;
    }
}
