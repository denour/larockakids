<?php

namespace Tests\Support;

/**
 * Deterministic stand-in for the global `random_int()` used by
 * `App\Services\OnboardingService::generateCode()`.
 *
 * The service resolves `random_int` unqualified from the `App\Services`
 * namespace, so `tests/Support/random_int_shadow.php` declares a namespaced
 * twin that delegates here. While nothing is armed this class simply forwards
 * to the real CSPRNG, so the rest of the suite is unaffected.
 */
final class FakeRandomInt
{
    /**
     * @var list<int>
     */
    private static array $queue = [];

    private static ?string $bound = null;

    /**
     * Hand out the given values, in order, to the next calls.
     */
    public static function queue(int ...$values): void
    {
        self::$queue = array_values($values);
        self::$bound = null;
    }

    /**
     * Always hand out the lowest ('min') or highest ('max') value the caller allows.
     */
    public static function returnBound(string $bound): void
    {
        self::$queue = [];
        self::$bound = $bound;
    }

    public static function reset(): void
    {
        self::$queue = [];
        self::$bound = null;
    }

    public static function next(int $min, int $max): int
    {
        if (self::$bound === 'min') {
            return $min;
        }

        if (self::$bound === 'max') {
            return $max;
        }

        if (self::$queue !== []) {
            return (int) array_shift(self::$queue);
        }

        return \random_int($min, $max);
    }
}
