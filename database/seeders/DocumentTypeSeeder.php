<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('barangay.document_types') as $type) {
            DocumentType::updateOrCreate(['document_type_name' => $type['document_type_name']], $type);
        }
    }
}
