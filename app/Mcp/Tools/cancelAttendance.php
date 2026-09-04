<?php

namespace App\Mcp\Tools;

use App\Enums\AttendanceStatus;
use App\Mcp\Concerns\FindsKidByName;
use App\Services\AttendanceScannerService;
use App\Services\TutorMessageService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class cancelAttendance extends Tool
{
    use FindsKidByName;

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Cancela la asistencia de HOY de un niño marcando su salida (estado
        "Retirado"): registra la hora de salida y envía el aviso de salida al
        tutor por WhatsApp. Al niño se le identifica por su nombre.
    MARKDOWN;

    public function handle(Request $request, AttendanceScannerService $scanner, TutorMessageService $messages): Response|ResponseFactory
    {
        $validated = $request->validate([
            'kid_name' => 'required|string|max:200',
        ], [
            'kid_name.required' => 'El nombre del niño es obligatorio.',
        ]);

        $kid = $this->resolveKid($validated['kid_name']);

        $attendance = $scanner->getActiveAttendanceToday($kid);

        if (! $attendance) {
            return Response::error("No hay una asistencia activa hoy para {$kid->full_name}.");
        }

        $attendance->update([
            'check_out' => now(),
            'status' => AttendanceStatus::RETIRADO,
        ]);

        $contact = $scanner->getPrimaryContact($kid);

        if ($contact) {
            $messages->sendExitMessage($contact, $kid);
        }

        return Response::structured([
            'message' => "Salida registrada para {$kid->full_name}.",
            'attendance_id' => $attendance->id,
            'kid' => $kid->only(['id', 'first_name', 'last_name']),
            'checked_out_at' => $attendance->check_out->toIso8601String(),
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
                ->description('Nombre del niño cuya asistencia de hoy se cancela (ej. "Giana Valentina").'),
        ];
    }
}
