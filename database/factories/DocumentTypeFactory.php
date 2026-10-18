<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentType> */
class DocumentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type_name' => 'DEMO '.fake()->unique()->uuid(),
            'document_type_fee' => null, 'processing_days' => null,
            'is_active' => true, 'is_required_other_documents' => false,
        ];
    }
}
