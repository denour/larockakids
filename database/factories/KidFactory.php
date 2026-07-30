<?php

namespace Database\Factories;

use App\Enums\GradeLevel;
use App\Enums\NapPreference;
use App\Enums\NotificationChannel;
use App\Enums\SphincterControl;
use App\Models\Allergy;
use App\Models\Contact;
use App\Models\Kid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kid>
 */
class KidFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Kid::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // 30% de probabilidad de que el cumpleaños sea este mes
        $birthDate = fake()->boolean(30)
            ? fake()->dateTimeBetween(
                startDate: "{$currentYear}-{$currentMonth}-01",
                endDate: "{$currentYear}-{$currentMonth}-".now()->daysInMonth
            )
            : fake()->dateTimeBetween('-10 years', 'now');

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => $birthDate,
            'gender' => fake()->randomElement(['male', 'female']),
            'grade_level' => fake()->randomElement(GradeLevel::cases())->value,
            'classroom' => 'Salón '.fake()->randomElement(['A', 'B', 'C']),
            'school_cycle' => now()->year.' – '.(now()->year + 1),
            'medical_conditions' => null,
            'medications' => null,
            'sphincter_control' => fake()->randomElement(SphincterControl::cases())->value,
            'nap' => fake()->randomElement(NapPreference::cases())->value,
            'routine_notes' => null,
            'wants_parents_group' => fake()->boolean(),
            'notification_channel' => fake()->randomElement(NotificationChannel::cases())->value,
        ];
    }

    /**
     * A kid in the final grade, about to graduate.
     */
    public function graduating(): self
    {
        return $this->state(fn (): array => [
            'grade_level' => GradeLevel::FourYears->value,
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterCreating(function (Kid $kid) {
            // Crear contactos si no existen
            if (Contact::count() === 0) {
                Contact::factory()->count(5)->create();
            }

            // Attach 1-3 random contacts
            $contacts = Contact::inRandomOrder()->take(rand(1, 3))->get();

            if ($contacts->isNotEmpty()) {
                $kid->contacts()->attach($contacts->first()->id, ['relationship_type' => 'parent']);

                if ($contacts->count() > 1) {
                    $kid->contacts()->attach($contacts->skip(1)->pluck('id'), ['relationship_type' => 'other']);
                }
            }

            // Crear alergias si no existen
            if (Allergy::count() === 0) {
                Allergy::factory()->count(5)->create();
            }

            // Attach 0-3 random allergies
            $allergies = Allergy::inRandomOrder()->take(rand(0, 3))->get();
            if ($allergies->isNotEmpty()) {
                $kid->allergies()->attach($allergies);
            }
        });
    }
}
