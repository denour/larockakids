<?php

namespace Database\Factories;

use App\Enums\QrCodeStatus;
use App\Models\Kid;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QrCode>
 */
class QrCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QrCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'code' => sprintf('LRK-%04d', $sequence),
            'kid_id' => null,
            'qr_image_path' => null,
            'status' => QrCodeStatus::Available,
            'assigned_at' => null,
        ];
    }

    /**
     * Mark the QR code as assigned to a kid.
     */
    public function assigned(?Kid $kid = null): static
    {
        return $this->state(fn (array $attributes) => [
            'kid_id' => $kid?->id ?? Kid::factory(),
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Mark the QR code as lost.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'kid_id' => null,
            'status' => QrCodeStatus::Lost,
            'assigned_at' => null,
        ]);
    }
}
