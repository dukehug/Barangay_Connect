<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\BarangayEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'staff_id' => fn (): int => BarangayEmployee::where('is_active', true)->inRandomOrder()->value('id') ?? BarangayEmployee::factory()->create()->id,
            'title' => fake()->sentence(5), 'content' => fake()->paragraph(),
            'category' => fake()->randomElement(['General', 'Emergency', 'Event']),
            'published' => fake()->boolean(),
            'posted_at' => fn (array $attributes) => $attributes['published'] ? fake()->dateTimeBetween('-1 month', 'now') : null,
        ];
    }
}
