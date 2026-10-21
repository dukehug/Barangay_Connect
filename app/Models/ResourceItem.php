<?php

namespace App\Models;

use Database\Factories\ResourceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceItem extends Model
{
    /** @use HasFactory<ResourceItemFactory> */
    use HasFactory;

    protected $fillable = ['name', 'kind', 'quantity', 'is_active', 'description'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'quantity' => 'integer'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(RequestReservation::class);
    }
}
