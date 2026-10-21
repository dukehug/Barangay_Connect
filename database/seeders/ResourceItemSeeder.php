<?php

namespace Database\Seeders;

use App\Models\ResourceItem;
use Illuminate\Database\Seeder;

class ResourceItemSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Chairs', 'Tables', 'Tents'] as $name) {
            ResourceItem::firstOrCreate(['name' => $name], ['kind' => 'equipment', 'quantity' => 0, 'is_active' => false, 'description' => 'Set the verified inventory quantity before enabling requests.']);
        }
    }
}
