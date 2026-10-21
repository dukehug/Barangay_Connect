<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\RequestReference;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Complaint extends Model implements HasMedia
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')->useDisk('private')->acceptsMimeTypes(RequestAttachment::MIME_TYPES);
    }

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            $record->reference = app(RequestReference::class)->generate($record->getAttributes()['created_at'] ?? null);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
        ];
    }

    protected $fillable = [
        'resident_id',
        'staff_id',
        'complaint_type',
        'complaint_type_id',
        'content',
        'attachment_url',
        'status',
        'review_note',
    ];

    public function latestStaffUpdate(): HasOne
    {
        return $this->hasOne(AuditLog::class, 'record_id')->ofMany(
            ['id' => 'max'],
            fn (Builder $query): Builder => $query->where('table_affected', 'complaints')
                ->where('action', 'updated')->whereNotNull('staff_id'),
        )->where('table_affected', 'complaints')->where('action', 'updated');
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintType::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BarangayEmployee::class, 'staff_id');
    }
}
