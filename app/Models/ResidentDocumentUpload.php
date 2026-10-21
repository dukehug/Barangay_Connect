<?php

namespace App\Models;

use Database\Factories\ResidentDocumentUploadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ResidentDocumentUpload extends Model implements HasMedia
{
    /** @use HasFactory<ResidentDocumentUploadFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use Prunable;

    protected $fillable = ['resident_id', 'kind', 'token'];

    protected static function booted(): void
    {
        static::creating(function (self $upload): void {
            $upload->token ??= (string) Str::uuid();
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')->useDisk('private')->acceptsMimeTypes(RequestAttachment::MIME_TYPES);
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDay());
    }
}
