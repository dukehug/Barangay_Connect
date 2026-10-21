<?php

namespace Database\Seeders;

use App\Models\ComplaintType;
use Illuminate\Database\Seeder;

class ComplaintTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Noise', 'Sanitation', 'Public Safety', 'Roads and Infrastructure', 'Barangay Services', 'Other'] as $index => $name) {
            ComplaintType::firstOrCreate(['name' => $name], ['is_active' => true, 'sort_order' => $index + 1]);
        }
    }
}
