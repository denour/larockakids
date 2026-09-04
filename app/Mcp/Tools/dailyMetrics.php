<?php

namespace App\Mcp\Tools;

use App\Enums\ServiceTime;
use App\Models\Attendance;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class dailyMetrics extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Devuelve el resumen de asistencia de un día: total de niños registrados,
        desglose por reunión (11 AM / 1 PM) y cuántos siguen dentro vs. cuántos
        ya salieron. Sin fecha, usa el día de hoy.
    MARKDOWN;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
        ], [
            'date.date' => 'La fecha no tiene un formato válido.',
        ]);

        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : Carbon::today();

        $attendances = Attendance::whereDate('check_in', $date)->get();

        return Response::structured([
            'date' => $date->toDateString(),
            'total' => $attendances->count(),
            'inside' => $attendances->whereNull('check_out')->count(),
            'left' => $attendances->whereNotNull('check_out')->count(),
            'by_service' => [
                ServiceTime::First->value => $attendances->where('service', ServiceTime::First)->count(),
                ServiceTime::Second->value => $attendances->where('service', ServiceTime::Second)->count(),
            ],
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
            'date' => $schema->string()->format('date')
                ->description('Día a resumir (YYYY-MM-DD). Si se omite, se usa hoy.'),
        ];
    }
}
