<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\BarangayEmployee;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentRequest> */
class DocumentRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'resident_id' => Resident::factory(),
            'document_type_id' => DocumentType::factory(),
            'purpose' => fake()->sentence(),
            'status' => fake()->randomElement(RequestStatus::cases()),
            'staff_id' => fn (array $attributes): ?int => in_array($attributes['status'], [RequestStatus::Pending, RequestStatus::Pending->value], true)
                ? null
                : (BarangayEmployee::where('is_active', true)->inRandomOrder()->value('id') ?? BarangayEmployee::factory()->create()->id),
            'created_at' => fake()->dateTimeBetween(now()->startOfMonth()->subMonths(5), now()),
        ];
    }
}
