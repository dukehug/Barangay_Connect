<?php

namespace Database\Factories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Resident> */
class ResidentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(), 'middle_name' => null, 'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(), 'password' => 'password',
            'gender' => fake()->randomElement(['Male', 'Female']),
            'birth_date' => fake()->dateTimeBetween('-75 years', '-18 years')->format('Y-m-d'),
            'marital_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Divorced']),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'contact_number' => fake()->unique()->numerify('09#########'),
            'resident_address' => fake()->address(),
        ];
    }
}
