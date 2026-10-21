<?php

namespace Database\Factories;

use App\Models\ResourceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResourceItem> */
class ResourceItemFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->words(3, true), 'kind' => 'equipment', 'quantity' => 100, 'is_active' => true];
    }
}
