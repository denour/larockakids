<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Kid;
use App\Models\Contact;
use App\Enums\Country;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Http;

class KidImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        // Asumimos que la primera fila es el encabezado
        $header = $collection->first();
        $rows = $collection->slice(1);

        foreach ($rows as $row) {
            $data = $header->combine($row);

            // Detectar género si no está presente
            $gender = $data['Genero'] ?? null;
            if (empty($gender) && !empty($data['Nombre niño'])) {
                try {
                    $response = Http::get('https://api.genderize.io', [
                        'name' => $data['Nombre niño'],
                        'country_id' => 'MX', // Opcional: puedes quitarlo si quieres que sea global
                    ]);
                    if ($response->ok() && $response->json('gender')) {
                        $gender = $response->json('gender');
                    } else {
                        $gender = 'male'; // Valor por defecto si no se puede determinar
                    }
                } catch (\Exception $e) {
                    $gender = 'male'; // Valor por defecto en caso de error
                }
            } elseif (empty($gender)) {
                $gender = 'male'; // Valor por defecto si no hay nombre
            }

            $kid = Kid::create([
                'first_name' => $data['Nombre niño'] ?? '',
                'last_name' => $data['Apellido'] ?? '',
                'birth_date' => (
                    isset($data['Fecha']) && trim($data['Fecha']) !== ''
                        ? (
                            is_numeric($data['Fecha'])
                                ? Carbon::instance(ExcelDate::excelToDateTimeObject($data['Fecha']))
                                : (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', trim($data['Fecha']))
                                    ? Carbon::createFromFormat(strlen(explode('/', trim($data['Fecha']))[2]) === 2 ? 'd/m/y' : 'd/m/Y', trim($data['Fecha']))
                                    : now()
                                )
                          )
                        : now()
                ),
                'gender' => $gender,
                'medical_notes' => $data['Necesidades Especiales'] ?? null,
                'is_active' => $data['Activo'] ?? 1,
            ]);

            // Crear o buscar el tutor
            if (!empty($data['Nombre tutor']) && !empty($data['Apellido Tutor']) && !empty($data['Teléfono'])) {
                $contact = Contact::firstOrCreate([
                    'first_name' => $data['Nombre tutor'],
                    'last_name' => $data['Apellido Tutor'],
                    'phone' => $data['Teléfono'],
                    'international_code' => Country::getDefaultCountry()->getCode(),
                ]);
                $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);
            }
        }
    }
}
