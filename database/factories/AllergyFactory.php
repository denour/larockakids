<?php

namespace Database\Factories;

use App\Models\Allergy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Allergy>
 */
class AllergyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Allergy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = [
            '#FF0000', // Rojo
            '#00FF00', // Verde
            '#0000FF', // Azul
            '#FFFF00', // Amarillo
            '#FF00FF', // Magenta
            '#00FFFF', // Cyan
            '#FFA500', // Naranja
            '#800080', // Púrpura
        ];

        return [
            'name' => fake()->unique()->word(),
            'color' => fake()->randomElement($colors),
        ];
    }
} 