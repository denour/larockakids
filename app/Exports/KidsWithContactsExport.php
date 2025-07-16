<?php

namespace App\Exports;

use App\Models\Kid;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KidsWithContactsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // Cargar relaciones para evitar N+1
        return Kid::with(['contacts', 'allergies'])->get();
    }

    public function headings(): array
    {
        return [
            'Nombre niño',
            'Apellido',
            'Fecha de Nacimiento',
            'Género',
            'Edad',
            'Nombre tutor',
            'Apellido Tutor',
            'Teléfono',
            'Alergias',
            'Necesidades Especiales',
        ];
    }

    public function map($kid): array
    {
        $contact = $kid->contacts->first();
        return [
            $kid->first_name,
            $kid->last_name,
            $kid->birth_date ? $kid->birth_date->format('j/n/y') : '',
            $kid->gender,
            $kid->age,
            $contact?->first_name ?? 'No',
            $contact?->last_name ?? 'No',
            $contact?->phone ?? 'No',
            $kid->allergies->isNotEmpty() ? $kid->allergies->pluck('name')->join(', ') : 'No',
            $kid->medical_notes ? $kid->medical_notes : 'No',
        ];
    }
} 