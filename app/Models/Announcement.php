<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Announcement extends Model implements HasMedia
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public const COVER_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->useDisk('public')
            ->acceptsMimeTypes(self::COVER_MIME_TYPES)
            ->useFallbackUrl(asset('resident/images/imus.jpg'))
            ->singleFile();
    }

    protected $fillable = [
        'staff_id',
        'title',
        'content',
        'category',
        'published',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BarangayEmployee::class, 'staff_id');
    }
}
