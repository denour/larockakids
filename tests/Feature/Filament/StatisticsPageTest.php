<?php

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('statistics page renders', function () {
    $this->get('/admin/statistics')
        ->assertSuccessful();
});

test('statistics page is accessible to authenticated users', function () {
    $this->get('/admin/statistics')
        ->assertStatus(200);
});
