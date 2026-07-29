<?php

namespace Tests\Fixtures;

use App\Mcp\Tools\createAttendance;
use Laravel\Mcp\Server;

/**
 * Minimal MCP server used only by CreateAttendanceToolTest.
 *
 * App\Mcp\Servers\AttendanceServer currently registers NO tools (its $tools
 * array is still the scaffolded empty stub), so createAttendance is not
 * reachable through it — see "KNOWN DEFECT #5" in the test. This fixture
 * registers the tool so the tool itself can be exercised through the real
 * MCP request path (tools/call → validation → handle → response) instead of
 * being poked at directly.
 */
class CreateAttendanceTestServer extends Server
{
    protected string $name = 'Attendance Server (testing)';

    protected string $version = '0.0.1';

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        createAttendance::class,
    ];
}
