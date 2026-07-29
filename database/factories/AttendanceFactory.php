<?php

namespace Database\Factories;

use App\Enums\ServiceTime;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $kid = Kid::inRandomOrder()->first() ?? Kid::factory()->create();
        $contact = $kid->contacts()->inRandomOrder()->first() ?? Contact::factory()->create();

        $checkIn = $this->faker->dateTimeBetween('-1 day', 'now');

        return [
            'kid_id' => $kid->id,
            'contact_id' => $contact->id,
            'check_in' => $checkIn,
            'check_out' => $this->faker->optional(0.7)->dateTimeBetween('now', '+8 hours'),
            'observations' => $this->faker->optional(0.3)->text(200),
            'service' => ServiceTime::fromHour((int) $checkIn->format('H')),
        ];
    }
}
