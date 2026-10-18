<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Database\Factories\DocumentRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    /** @use HasFactory<DocumentRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
        ];
    }

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
