<?php

use App\Models\User;

test('the login screen carries the onboarding branding', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('images/brand/piedritas-kids.png', false)
        ->assertSee('fonts/baloo2-latin.woff2', false)
        ->assertSee('pk-fondo', false);
});

/**
 * El branding sobrescribe la rampa --primary-* y esconde el logo del panel.
 * Si se filtrara al panel autenticado, teñiría de azul toda la aplicación.
 */
test('the branding does not leak into the authenticated panel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('pk-fondo', false)
        ->assertDontSee('--primary-600: 47, 91, 214', false);
});
