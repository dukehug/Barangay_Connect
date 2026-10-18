<?php

namespace Database\Factories;

use App\Enums\EmployeeRole;
use App\Models\BarangayEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BarangayEmployee> */
class BarangayEmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(),
            'middle_name' => null, 'email' => fake()->unique()->safeEmail(),
            'password' => 'password', 'role' => EmployeeRole::Staff, 'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => EmployeeRole::Admin]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
