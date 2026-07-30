<?php

namespace Database\Factories;

use App\Models\OnboardingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnboardingSession>
 */
class OnboardingSessionFactory extends Factory
{
    protected $model = OnboardingSession::class;

    public function definition(): array
    {
        return [
            'code' => (string) fake()->numberBetween(100000, 999999),
            'status' => 'pending',
            'kid_id' => null,
            'phone' => null,
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function matched(int $kidId, string $phone): self
    {
        return $this->state(fn (): array => [
            'status' => 'matched',
            'kid_id' => $kidId,
            'phone' => $phone,
        ]);
    }
}
