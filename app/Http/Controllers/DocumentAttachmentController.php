<?php

namespace App\Http\Controllers;

use App\DocumentWorkflow;
use App\Models\Complaint;
use App\Models\RequestAttachment;
use App\Models\ResidentDocumentUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentAttachmentController extends Controller
{
    public function staffComplaintAttachment(Request $request, Complaint $complaint): StreamedResponse
    {
        $employee = $request->user('employee');
        abort_unless($employee?->is_active && Gate::forUser($employee)->allows('view', $complaint), 403);
        abort_unless($complaint->attachment_url, 404);
        $disk = Storage::disk('local')->exists($complaint->attachment_url) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($complaint->attachment_url), 404);

        return Storage::disk($disk)->response($complaint->attachment_url, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);
    }

    public function preview(Request $request): StreamedResponse
    {
        $data = $request->validate(['path' => ['required', 'string', 'max:1024']]);
        $media = Media::query()->whereIn('model_type', [(new Complaint)->getMorphClass(), (new RequestAttachment)->getMorphClass(), (new ResidentDocumentUpload)->getMorphClass()])
            ->where('disk', 'private')->find(explode('/', $data['path'])[0]);
        if ($media?->model instanceof ResidentDocumentUpload && $media->getPathRelativeToRoot() === $data['path']) {
            abort_unless($request->user('resident')?->id === $media->model->resident_id && $media->model->created_at->gte(now()->subDay()), 403);

            return Storage::disk('private')->response($media->getPathRelativeToRoot(), $media->file_name, [
                'Content-Type' => $media->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
            ]);
        }
        if ($media?->model instanceof Complaint && $media->getPathRelativeToRoot() === $data['path']) {
            $employee = $request->user('employee');
            abort_unless($request->user('resident')?->id === $media->model->resident_id
                || ($employee?->is_active && Gate::forUser($employee)->allows('view', $media->model)), 403);

            return Storage::disk('private')->response($media->getPathRelativeToRoot(), $media->file_name, [
                'Content-Type' => $media->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
                'Content-Security-Policy' => "frame-ancestors 'self'",
            ]);
        }
        $attachment = $media && $media->getPathRelativeToRoot() === $data['path']
            ? $media->model
            : RequestAttachment::query()->where('path', $data['path'])->first();
        abort_unless($attachment && $attachment->filePath() === $data['path'], 404);
        $resident = $request->user('resident');
        $employee = $request->user('employee');
        abort_unless(($resident && $resident->id === $attachment->documentRequest->resident_id)
            || ($employee?->is_active && Gate::forUser($employee)->allows('view', $attachment->documentRequest)), 403);
        abort_unless(Storage::disk('private')->exists($attachment->filePath()), 404);
        abort_unless(in_array($attachment->mimeType(), RequestAttachment::MIME_TYPES, true), 415);

        return Storage::disk('private')->response($attachment->filePath(), $attachment->name, [
            'Content-Type' => $attachment->mimeType(),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function destroy(Request $request, RequestAttachment $attachment, DocumentWorkflow $workflow): RedirectResponse
    {
        abort_unless($attachment->documentRequest->resident_id === $request->user('resident')->id, 404);
        $document = $attachment->documentRequest;
        $workflow->locked(function () use ($document, $attachment, $workflow): void {
            $document->refresh();
            abort_unless($document->isEditable(), 403);
            $workflow->record($document, 'Resident deleted attachment', null, null, ['attachment_id' => $attachment->id, 'name' => $attachment->name]);
            $legacyPath = $attachment->getFirstMedia('document')?->getCustomProperty('legacy_private_path')
                ?? ($attachment->hasMedia('document') ? null : $attachment->path);
            $attachment->delete();
            if ($legacyPath) {
                Storage::disk('private')->delete($legacyPath);
            }
        });

        return redirect()->route('resident.documents.show', $document)->with('status', 'Attachment deleted.');
    }

    public function reorder(Request $request, RequestAttachment $attachment, DocumentWorkflow $workflow): RedirectResponse
    {
        abort_unless($attachment->documentRequest->resident_id === $request->user('resident')->id, 404);
        $data = $request->validate(['direction' => ['required', 'in:up,down']]);
        $workflow->locked(function () use ($attachment, $data): void {
            $attachments = $attachment->documentRequest->attachments()->lockForUpdate()->get();
            $position = $attachments->search(fn (RequestAttachment $item): bool => $item->id === $attachment->id);
            $other = $position + ($data['direction'] === 'up' ? -1 : 1);
            if ($position === false || ! $attachments->has($other)) {
                return;
            }
            $items = $attachments->all();
            [$items[$position], $items[$other]] = [$items[$other], $items[$position]];
            foreach ($items as $index => $item) {
                $item->update(['order_column' => $index + 1]);
            }
        });

        return redirect()->route('resident.documents.show', $attachment->documentRequest)->with('status', 'Attachment order updated.');
    }
}
