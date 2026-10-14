<?php

namespace Database\Factories;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.7)->lastName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-5 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(array_column(GenderEnum::cases(), 'value')),
            'contact_number' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'medical_history' => fake()->optional()->sentence(10),
            'allergies' => fake()->optional()->words(3, true),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_number' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}