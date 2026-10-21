<?php

namespace Database\Factories;

use App\Models\Resident;
use App\Models\ResidentDocumentUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResidentDocumentUpload>
 */
class ResidentDocumentUploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resident_id' => Resident::factory(), 'kind' => 'photo_id',
        ];
    }
}
