<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => 'EMP-' . $this->faker->unique()->numberBetween(1000, 9999),

            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'middle_name' => $this->faker->optional()->lastName(),

            'position' => $this->faker->randomElement([
                'CASHIER',
                'HEAD_STAFF',
                'DELIVIRY_RIDER',
                'BAKER',
            ]),

            'salary_type' => $this->faker->randomElement([
                'HOURLY',
                'DAILY',
                'QUOTA',
            ]),
        ];
    }
}
