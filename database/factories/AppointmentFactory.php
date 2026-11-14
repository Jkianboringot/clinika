<?php

namespace Database\Factories;

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsApproveEnum;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'staff_id' => fake()->boolean(70) ? User::factory() : null, // nullable
            'dentist_id' => User::factory(),
            'patient_id' => Patient::factory(),
            'procedure_id' => Procedure::factory(),
            'description' => fake()->optional(0.6)->sentence(),
            'appointment_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'appointment_time' => fake()->randomElement([
                '08:00:00', '09:00:00', '10:30:00', '13:00:00', '14:30:00', '16:00:00',
            ]),
            'status' => fake()->randomElement(AppointmentStatusEnum::cases())->value,
            'is_approve' => fake()->randomElement(IsApproveEnum::cases())->value,
        ];
    }

    // handy states
    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatusEnum::Scheduled->value,
            'is_approve' => IsApproveEnum::Pending->value,
        ]);
    }
}