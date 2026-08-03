<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Autoriza la tablet del kiosco para las rutas de scanner.
 */
function authorizeKiosk(\Illuminate\Testing\TestCase|\Tests\TestCase $test): void
{
    config(['kiosk.token' => 'kiosk-testing-token']);

    // withCredentials: las peticiones JSON de los tests no mandan cookies sin él,
    // aunque el fetch del navegador sí las manda (credentials same-origin).
    $test->withCredentials()->withCookie(config('kiosk.cookie'), 'kiosk-testing-token');
}

function createKidWithContact(array $kidData = [], array $contactData = [], string $relationship = 'parent'): array
{
    $contact = \App\Models\Contact::factory()->create($contactData);
    $kid = \App\Models\Kid::factory()->create($kidData);
    $kid->contacts()->sync([]);
    $kid->contacts()->attach($contact->id, ['relationship_type' => $relationship]);

    return ['kid' => $kid, 'contact' => $contact];
}

function createAssignedQr(\App\Models\Kid $kid, string $code = 'TEST-0001'): \App\Models\QrCode
{
    return \App\Models\QrCode::factory()->create([
        'code' => $code,
        'kid_id' => $kid->id,
        'status' => \App\Enums\QrCodeStatus::Assigned,
        'assigned_at' => now(),
    ]);
}
