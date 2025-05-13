<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kid;
use Illuminate\Support\Facades\File;

class ExportKidsData extends Command
{
    protected $signature = 'kids:export';
    protected $description = 'Exporta los datos de la tabla kids a un archivo JSON';

    public function handle()
    {
        $this->info('Exportando datos de kids...');

        $kids = Kid::with('contacts')->get()->map(function ($kid) {
            return [
                'Nombre' => $kid->first_name . ' ' . $kid->last_name,
                'Tutor' => $kid->contacts->first() ? $kid->contacts->first()->first_name . ' ' . $kid->contacts->first()->last_name : '',
                'Telefono' => $kid->contacts->first() ? $kid->contacts->first()->phone : '',
                'Fecha Nacimiento' => $kid->birth_date ? $kid->birth_date->format('d/m/Y') : null,
                'Observaciones' => $kid->medical_notes,
                'Activo' => $kid->is_active,
                'Genero' => $kid->gender
            ];
        });

        $jsonPath = database_path('seeders/data/Kids_Export.json');
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($kids, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Datos exportados exitosamente a: ' . $jsonPath);
    }
} 