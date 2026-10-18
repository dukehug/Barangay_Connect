<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    protected $fillable = [
        'resident_id',
        'document_type_id',
        'staff_id',
        'purpose',
        'status',

    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BarangayEmployee::class, 'staff_id');
    }
}
