<?php

use App\Models\Kid;
use App\Models\User;

test('guests are redirected to the staff login for every onboarding route', function (string $method, string $route) {
    $kid = Kid::factory()->create();
    $url = str_contains($route, '{kid}') ? str_replace('{kid}', (string) $kid->id, $route) : $route;

    $this->call($method, $url)
        ->assertRedirect(route('filament.admin.auth.login'));
})->with([
    ['get', '/onboarding'],
    ['get', '/onboarding/search'],
    ['get', '/onboarding/register'],
    ['get', '/onboarding/{kid}/confirm'],
    ['get', '/onboarding/{kid}/edit'],
    ['get', '/onboarding/{kid}/done'],
]);

test('an authenticated staff user can open the kiosk entry screen', function () {
    $this->actingAs(User::factory()->create())
        ->get('/onboarding')
        ->assertOk()
        ->assertSee('Encontraremos a tus hijos');
});

test('the webhook routes stay public (no auth required)', function () {
    $this->get('/webhooks/whatsapp')->assertStatus(403); // reachable, just rejects bad verify token
    $this->postJson('/webhooks/whatsapp', [])->assertOk();
});
