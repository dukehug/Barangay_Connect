<?php

namespace App\Models;

use Database\Factories\RequestReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestReservation extends Model
{
    /** @use HasFactory<RequestReservationFactory> */
    use HasFactory;

    protected $fillable = ['resource_item_id', 'requested_quantity', 'approved_quantity', 'issued_quantity', 'returned_quantity', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function resourceItem(): BelongsTo
    {
        return $this->belongsTo(ResourceItem::class);
    }

    public function outstanding(): int
    {
        return $this->issued_quantity - $this->returned_quantity;
    }

    public function isOverdue(): bool
    {
        return $this->outstanding() > 0 && $this->ends_at->isPast();
    }
}
