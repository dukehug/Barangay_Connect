<?php

namespace App\Console\Commands;

use App\DocumentWorkflow;
use App\Models\RequestAttachment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportDocumentAttachments extends Command
{
    protected $signature = 'documents:import-attachments';

    protected $description = 'Copy legacy private attachments into Media Library without removing originals';

    public function handle(): int
    {
        $failed = 0;
        RequestAttachment::query()->doesntHave('media')->chunkById(100, function (Collection $attachments) use (&$failed): void {
            foreach ($attachments as $attachment) {
                try {
                    app(DocumentWorkflow::class)->locked(function () use ($attachment): void {
                        $attachment->refresh();
                        if ($attachment->hasMedia('document')) {
                            return;
                        }
                        if (! Storage::disk('private')->exists($attachment->path)) {
                            throw new \RuntimeException('Original file is missing.');
                        }
                        $media = $attachment->addMediaFromDisk($attachment->path, 'private')->preservingOriginal()
                            ->withCustomProperties(['legacy_private_path' => $attachment->path])
                            ->usingName(pathinfo($attachment->name, PATHINFO_FILENAME))->toMediaCollection('document', 'private');
                        $attachment->update(['path' => $media->getPathRelativeToRoot(), 'order_column' => $attachment->id]);
                    });
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error('Attachment #'.$attachment->id.': '.$exception->getMessage());
                }
            }
        });
        $this->info('Import complete. Original private files retained. Failures: '.$failed);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
