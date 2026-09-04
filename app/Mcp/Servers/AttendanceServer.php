<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;

class AttendanceServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Attendance Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Servidor de asistencia de La Roca Kids. Herramientas:

        - createAttendance: registra la entrada de un niño (crea niño/contacto si
          no existen) y avisa al tutor.
        - cancelAttendance: cancela la asistencia de HOY de un niño (marca su
          salida) y avisa al tutor. Identifica al niño por nombre.
        - sendAssistance: envía al tutor el aviso de asistencia por WhatsApp.
          Identifica al niño por nombre.
        - dailyMetrics: resumen de asistencia del día (total, por reunión,
          dentro vs. salidos).

        Cuando varios niños coincidan con un nombre, pide nombre y apellido.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        \App\Mcp\Tools\createAttendance::class,
        \App\Mcp\Tools\cancelAttendance::class,
        \App\Mcp\Tools\sendAssistance::class,
        \App\Mcp\Tools\dailyMetrics::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
