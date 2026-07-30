<?php

namespace App\Services;

use Tests\Support\FakeRandomInt;

if (! function_exists('App\Services\random_int')) {
    /**
     * Namespaced twin of the global `random_int()`.
     *
     * PHP resolves unqualified function calls against the current namespace
     * before falling back to the global one, so every `random_int()` call made
     * from inside `App\Services` lands here. When `FakeRandomInt` is not armed
     * it forwards to the real CSPRNG, so behaviour is unchanged by default.
     */
    function random_int(int $min, int $max): int
    {
        return FakeRandomInt::next($min, $max);
    }
}
