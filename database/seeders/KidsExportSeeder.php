<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kid;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Support\Str;

class KidsExportSeeder extends Seeder
{
    private function capitalizeName($name)
    {
        return Str::title(mb_strtolower($name));
    }

    public function run()
    {
        $jsonPath = database_path('seeders/data/Kids_Export.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        foreach ($data as $item) {
            $fullName = $item['Nombre'] ?? '';
            $nameParts = explode(' ', $fullName);
            $firstName = $this->capitalizeName($nameParts[0] ?? '');
            $lastName = $this->capitalizeName(implode(' ', array_slice($nameParts, 1)) ?? '');

            $fullTutorName = $item['Tutor'] ?? '';
            $tutorParts = explode(' ', $fullTutorName);
            $tutorFirstName = $this->capitalizeName($tutorParts[0] ?? '');
            $tutorLastName = $this->capitalizeName(implode(' ', array_slice($tutorParts, 1)) ?? '');

            // Crear el niño
            $kid = Kid::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birth_date' => isset($item['Fecha Nacimiento']) ? Carbon::createFromFormat('d/m/Y', $item['Fecha Nacimiento']) : null,
                'gender' => $item['Genero'] ?? 'male',
                'medical_notes' => $item['Observaciones'] ?? null,
                'is_active' => $item['Activo'] ?? 1,
            ]);

            // Crear el contacto (tutor)
            $contact = Contact::create([
                'first_name' => $tutorFirstName,
                'last_name' => $tutorLastName,
                'phone' => $item['Telefono'] ?? '',
                'email' => null,
            ]);

            $kid->contacts()->attach($contact, ['relationship_type' => 'parent']);
        }
    }
} 