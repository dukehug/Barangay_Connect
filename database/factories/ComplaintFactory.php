<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Complaint> */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'resident_id' => Resident::factory(),
            'complaint_type' => fake()->randomElement(['Noise', 'Sanitation', 'Other']),
            'content' => fake()->paragraph(), 'attachment_url' => null,
            'status' => fake()->randomElement(ComplaintStatus::cases()),
            'staff_id' => fn (array $attributes): ?int => in_array($attributes['status'], [ComplaintStatus::Pending, ComplaintStatus::Pending->value], true)
                ? null
                : (BarangayEmployee::where('is_active', true)->inRandomOrder()->value('id') ?? BarangayEmployee::factory()->create()->id),
            'created_at' => fake()->dateTimeBetween(now()->startOfMonth()->subMonths(5), now()),
        ];
    }
}
