<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
{
    protected $model = Treatment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // doctor
            'appointment_id' => Appointment::factory(),
            'follow_up_schedule' => fake()->optional(0.6)->randomElement([
                '1 week',
                '2 weeks',
                '1 month',
                '3 months',
                '6 months',
            ]),
            'duration_minutes' => fake()->optional(0.8)->randomElement([15, 30, 45, 60, 90]),
            'summary' => fake()->optional(0.8)->paragraph(),
        ];
    }
}