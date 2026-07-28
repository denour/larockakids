<?php

namespace App\Mcp\Tools;

use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Services\TutorMessageService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class createAttendance extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Creates an attendance record for a kid with their contact information.
        This tool helps to register when a kid arrives at the facility and notifies their contact.
        It also handles the creation of new kids and contacts if they don't exist in the system.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        // ✅ Validación de los datos
        $validated = $request->validate([
            'kid.first_name' => 'required|string|max:100',
            'kid.last_name' => 'required|string|max:100',
            'kid.birth_date' => 'nullable|date',
            'kid.gender' => 'nullable|in:male,female,not_specified',

            'contact.first_name' => 'required|string|max:100',
            'contact.last_name' => 'required|string|max:100',
            'contact.phone' => 'required|string|max:20',
            'contact.email' => 'nullable|email|max:150',
            'contact.international_code' => 'nullable|string|max:5',

            'observations' => 'nullable|string|max:500',
        ], [
            'kid.first_name.required' => 'El nombre del niño es obligatorio.',
            'kid.last_name.required' => 'El apellido del niño es obligatorio.',
            'contact.phone.required' => 'El número de teléfono del tutor es obligatorio.',
            'contact.email.email' => 'El correo electrónico no tiene un formato válido.',
        ]);

        // Extraer datos ya validados
        $kidData = $validated['kid'];
        $contactData = $validated['contact'];
        $observations = $validated['observations'] ?? null;

        // Buscar o crear el niño
        $kid = Kid::firstOrCreate(
            ['first_name' => $kidData['first_name'], 'last_name' => $kidData['last_name']],
            [
                'birth_date' => $kidData['birth_date'] ?? null,
                'gender' => $kidData['gender'] ?? 'not_specified',
            ]
        );

        $contact = Contact::firstOrCreate(
            ['phone' => $contactData['phone']],
            [
                'first_name' => $contactData['first_name'],
                'last_name' => $contactData['last_name'],
                'email' => $contactData['email'] ?? null,
                'international_code' => $contactData['international_code'] ?? '+52',
            ]
        );

        if (! $kid->contacts()->where('contact_id', $contact->id)->exists()) {
            $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);
        }

        $attendance = Attendance::create([
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => now(),
            'observations' => $observations,
        ]);

        $tutorMessageService = app(TutorMessageService::class);

        if ($kid->wasRecentlyCreated && $contact->wasRecentlyCreated) {
            $tutorMessageService->sendWelcomeMessage($contact, $kid);
        } else {
            $tutorMessageService->sendEntryMessage($contact, $kid);
        }

        return Response::success([
            'message' => 'Asistencia registrada exitosamente',
            'attendance_id' => $attendance->id,
            'kid' => $kid,
            'contact' => $contact,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'kid' => $schema->object([
                'first_name' => $schema->string()->required()->description('Nombre del niño'),
                'last_name' => $schema->string()->required()->description('Apellidos del niño'),
                'birth_date' => $schema->string()->format('date')->description('Fecha de nacimiento'),
                'gender' => $schema->string()->enum(['male', 'female', 'not_specified'])->description('Género del niño'),
            ]),
            'contact' => $schema->object([
                'first_name' => $schema->string()->required()->description('Nombre del contacto'),
                'last_name' => $schema->string()->required()->description('Apellidos del contacto'),
                'phone' => $schema->string()->required()->description('Teléfono del contacto'),
                'email' => $schema->string()->format('email')->description('Correo electrónico del contacto'),
                'international_code' => $schema->string()->description('Código internacional del teléfono'),
            ]),
            'observations' => $schema->string()->description('Observaciones adicionales de la asistencia'),
        ];
    }
}
