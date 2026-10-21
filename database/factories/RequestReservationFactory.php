<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use App\Models\RequestReservation;
use App\Models\ResourceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RequestReservation> */
class RequestReservationFactory extends Factory
{
    public function definition(): array
    {
        return ['document_request_id' => DocumentRequest::factory(), 'resource_item_id' => ResourceItem::factory(), 'requested_quantity' => 10, 'approved_quantity' => 10, 'starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(8)];
    }
}
