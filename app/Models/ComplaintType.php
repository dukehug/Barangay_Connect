<?php

namespace App\Models;

use Database\Factories\ComplaintTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintType extends Model
{
    /** @use HasFactory<ComplaintTypeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
