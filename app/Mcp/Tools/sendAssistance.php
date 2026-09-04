<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FindsKidByName;
use App\Services\AttendanceScannerService;
use App\Services\TutorMessageService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class sendAssistance extends Tool
{
    use FindsKidByName;

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Envía al tutor (papá/mamá) del niño el mensaje de asistencia por
        WhatsApp: le avisa que su niño requiere que lo acompañen. Al niño se le
        identifica por su nombre; el mensaje se manda al contacto principal.
    MARKDOWN;

    public function handle(Request $request, AttendanceScannerService $scanner, TutorMessageService $messages): Response|ResponseFactory
    {
        $validated = $request->validate([
            'kid_name' => 'required|string|max:200',
        ], [
            'kid_name.required' => 'El nombre del niño es obligatorio.',
        ]);

        $kid = $this->resolveKid($validated['kid_name']);

        $contact = $scanner->getPrimaryContact($kid);

        if (! $contact) {
            return Response::error("No se encontró un contacto para {$kid->full_name}.");
        }

        $messages->sendAssistanceMessage($contact, $kid);

        return Response::structured([
            'message' => "Notificación de asistencia enviada a {$contact->full_name}.",
            'kid' => $kid->only(['id', 'first_name', 'last_name']),
            'contact' => $contact->only(['id', 'first_name', 'last_name']),
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
            'kid_name' => $schema->string()->required()
                ->description('Nombre del niño cuyo tutor recibirá el aviso de asistencia (ej. "Giana Valentina").'),
        ];
    }
}
