<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RequestAttachment extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    public const KINDS = [
        'request_letter' => 'Request letter',
        'registration' => 'Copy of DTI or SEC registration',
        'previous_clearance' => 'Previous year’s Barangay Clearance',
        'voters_id' => 'Voter’s ID',
        'voter_registration_record' => 'Voter Registration Record (VRR) issued by COMELEC',
        'nbi_clearance' => 'NBI clearance',
        'photo_id' => 'Another valid ID with a photo',
    ];

    protected $fillable = ['kind', 'path', 'name', 'order_column'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')->useDisk('private')->acceptsMimeTypes(self::MIME_TYPES)->singleFile();
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function filePath(): string
    {
        return $this->getFirstMedia('document')?->getPathRelativeToRoot() ?? $this->path;
    }

    public function mimeType(): string
    {
        return $this->getFirstMedia('document')?->mime_type
            ?? (Storage::disk('private')->mimeType($this->path) ?: 'application/octet-stream');
    }

    public function previewUrl(): string
    {
        return Storage::disk('private')->temporaryUrl($this->filePath(), now()->addMinutes(30));
    }

    public function isImage(): bool
    {
        return in_array($this->mimeType(), ['image/jpeg', 'image/png'], true);
    }
}
