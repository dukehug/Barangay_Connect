<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Database\Factories\DocumentRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'confirmation_expires_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'rbi_verified_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'amount_paid' => 'decimal:2',
        ];
    }

    protected $fillable = [
        'resident_id',
        'document_type_id',
        'staff_id',
        'purpose',
        'category', 'application_kind', 'review_note', 'confirmation_expires_at', 'accepted_at', 'rbi_verified_at', 'official_receipt', 'amount_paid', 'paid_at',
        'status',

    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(RequestReservation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class)->orderBy('order_column')->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class)->orderByDesc('id');
    }

    public function workflowCategory(): string
    {
        return $this->category ?? $this->documentType->category;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [RequestStatus::NeedsRevision, RequestStatus::Expired], true);
    }

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
