<?php

use App\Models\ApiToken;
use App\Models\Kid;

/**
 * Crea un token con los permisos dados y devuelve su valor en claro.
 *
 * @param  array<int, string>|null  $abilities
 */
function plainApiToken(?array $abilities): string
{
    return ApiToken::generate('test', $abilities)['plain_token'];
}

/**
 * @return array<string, mixed>
 */
function mcpInitializePayload(): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0'],
        ],
    ];
}

// =============================================================
// Cada ruta exige su permiso
// =============================================================

test('each api route accepts a token with its ability', function (string $method, string $uri, string $ability) {
    $kid = Kid::factory()->create(['gender' => 'male']);
    $uri = str_replace('{id}', (string) $kid->id, $uri);

    $this->withToken(plainApiToken([$ability]))
        ->json($method, $uri, ['gender' => 'female'])
        ->assertOk();
})->with([
    'listar niños' => ['GET', '/api/v1/kids', 'kids:read'],
    'editar niño' => ['PATCH', '/api/v1/kids/{id}', 'kids:write'],
    'asistencias' => ['GET', '/api/v1/attendance', 'attendance:read'],
    'exportar' => ['GET', '/api/v1/export/kids', 'export'],
]);

test('each api route rejects a token that lacks its ability', function (string $method, string $uri, string $wrongAbility) {
    $kid = Kid::factory()->create(['gender' => 'male']);
    $uri = str_replace('{id}', (string) $kid->id, $uri);

    $this->withToken(plainApiToken([$wrongAbility]))
        ->json($method, $uri, ['gender' => 'female'])
        ->assertForbidden();

    expect($kid->refresh()->gender)->toBe('male');
})->with([
    'listar niños con permiso de asistencias' => ['GET', '/api/v1/kids', 'attendance:read'],
    'editar niño con solo lectura' => ['PATCH', '/api/v1/kids/{id}', 'kids:read'],
    'asistencias con permiso de niños' => ['GET', '/api/v1/attendance', 'kids:read'],
    'exportar con solo lectura' => ['GET', '/api/v1/export/kids', 'kids:read'],
    'corregir géneros con permiso de escritura' => ['POST', '/api/v1/admin/fix-genders', 'kids:write'],
]);

test('fix-genders requires full access', function () {
    $this->withToken(plainApiToken(['*']))
        ->postJson('/api/v1/admin/fix-genders')
        ->assertOk();
});

test('a full access token passes every ability check', function () {
    $kid = Kid::factory()->create(['gender' => 'male']);

    $this->withToken(plainApiToken(['*']))
        ->patchJson("/api/v1/kids/{$kid->id}", ['gender' => 'female'])
        ->assertOk();

    expect($kid->refresh()->gender)->toBe('female');
});

test('a token without abilities is rejected everywhere', function (?array $abilities) {
    $token = plainApiToken($abilities);

    $this->withToken($token)->getJson('/api/v1/kids')->assertForbidden();
    $this->withToken($token)->postJson('/api/v1/admin/fix-genders')->assertForbidden();
})->with([
    'lista vacía' => [[]],
    'null' => [null],
]);

test('requests without a token or with an unknown one still get 401', function () {
    $this->getJson('/api/v1/kids')->assertUnauthorized();

    $this->withToken('no-existe')->getJson('/api/v1/kids')->assertUnauthorized();
});

// =============================================================
// Servidor MCP: puede registrar asistencias y avisar a tutores
// =============================================================

test('the mcp server rejects a read only token', function () {
    $this->withToken(plainApiToken(['kids:read', 'attendance:read', 'export']))
        ->postJson('/mcp/attendance', mcpInitializePayload())
        ->assertForbidden();
});

test('the mcp server accepts a token with attendance:write', function () {
    $this->withToken(plainApiToken(['attendance:write']))
        ->postJson('/mcp/attendance', mcpInitializePayload())
        ->assertOk()
        ->assertJsonPath('result.serverInfo.name', 'Attendance Server');
});

test('the token form offers every ability the routes ask for', function () {
    expect(array_keys(ApiToken::ABILITIES))->toEqualCanonicalizing([
        '*', 'kids:read', 'kids:write', 'attendance:read', 'attendance:write', 'export',
    ]);
});
