<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Contact;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Crear usuario de Filament
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@larocakids.com',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            AllergySeeder::class,
            AttendanceSeeder::class,
            TutorMessageSeeder::class,
            KidsExportSeeder::class,
        ]);
    }
}
