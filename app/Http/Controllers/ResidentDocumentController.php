<?php

namespace App\Http\Controllers;

use App\DocumentWorkflow;
use App\Enums\RequestStatus;
use App\Http\Requests\ResidentDocumentRequest;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\RequestAttachment;
use App\Models\ResidentDocumentUpload;
use App\Models\ResourceItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ResidentDocumentController extends Controller
{
    public function create(): View
    {
        return $this->form(new DocumentRequest);
    }

    public function show(Request $request, DocumentRequest $document, DocumentWorkflow $workflow): View
    {
        $this->owned($request, $document);
        $workflow->expire();

        return view('resident.document-detail', ['document' => $document->refresh()->load('documentType', 'attachments', 'reservations.resourceItem', 'events')]);
    }

    public function edit(Request $request, DocumentRequest $document): View
    {
        $this->owned($request, $document);
        abort_unless($document->isEditable(), 403);

        return $this->form($document);
    }

    private function form(DocumentRequest $document): View
    {
        return view('resident.document-form', [
            'document' => $document->load('attachments', 'reservations'),
            'documentTypes' => DocumentType::where('is_active', true)->orWhere('id', $document->document_type_id)->orderBy('document_type_name')->get(),
            'resources' => ResourceItem::where('is_active', true)->orderBy('kind')->orderBy('name')->get(),
        ]);
    }

    public function store(ResidentDocumentRequest $request, DocumentWorkflow $workflow): RedirectResponse
    {
        return $this->persist($request, new DocumentRequest(['resident_id' => $request->user('resident')->id]), $workflow);
    }

    public function update(ResidentDocumentRequest $request, DocumentRequest $document, DocumentWorkflow $workflow): RedirectResponse
    {
        $this->owned($request, $document);

        return $this->persist($request, $document, $workflow);
    }

    private function persist(ResidentDocumentRequest $request, DocumentRequest $document, DocumentWorkflow $workflow): RedirectResponse
    {
        $stored = [];
        try {
            $document = $workflow->locked(function () use ($request, $document, $workflow, &$stored): DocumentRequest {
                $resubmitting = $document->exists;
                if ($resubmitting) {
                    $document = DocumentRequest::query()->lockForUpdate()->findOrFail($document->id);
                    if (! $document->isEditable()) {
                        throw ValidationException::withMessages(['request' => 'This request can no longer be edited.']);
                    }
                }
                $data = $request->validated();
                $document->fill([
                    'document_type_id' => $data['document_type_id'], 'purpose' => $data['purpose'],
                    'category' => $resubmitting ? $document->workflowCategory() : DocumentType::findOrFail($data['document_type_id'])->category,
                    'application_kind' => $data['application_kind'] ?? null, 'status' => RequestStatus::Pending,
                    'review_note' => null, 'confirmation_expires_at' => null, 'accepted_at' => null,
                ])->save();
                $document->reservations()->delete();
                foreach ($data['reservations'] ?? [] as $line) {
                    $document->reservations()->create([
                        ...$line,
                        'starts_at' => CarbonImmutable::parse($line['starts_at'])->setTimezone(config('app.timezone')),
                        'ends_at' => CarbonImmutable::parse($line['ends_at'])->setTimezone(config('app.timezone')),
                    ]);
                }
                foreach ($request->file('files', []) as $kind => $file) {
                    if ($file === null) {
                        continue;
                    }
                    $attachment = $document->attachments()->create([
                        'kind' => $kind, 'path' => '', 'name' => $file->getClientOriginalName(),
                        'order_column' => ($document->attachments()->max('order_column') ?? 0) + 1,
                    ]);
                    $media = $attachment->addMedia($file)->toMediaCollection('document', 'private');
                    $stored[] = $media->getPathRelativeToRoot();
                    $attachment->update(['path' => $media->getPathRelativeToRoot()]);
                }
                foreach ($data['uploads'] ?? [] as $kind => $token) {
                    if (! $token) {
                        continue;
                    }
                    $upload = ResidentDocumentUpload::where('token', $token)->where('resident_id', $request->user('resident')->id)
                        ->where('kind', $kind)->where('created_at', '>=', now()->subDay())->lockForUpdate()->first();
                    $uploadedFiles = $upload?->getMedia('document');
                    if (! $uploadedFiles || $uploadedFiles->isEmpty()) {
                        throw ValidationException::withMessages(['uploads.'.$kind => 'This upload expired. Please upload the file again.']);
                    }
                    foreach ($uploadedFiles as $media) {
                        $attachment = $document->attachments()->create([
                            'kind' => $kind, 'path' => $media->getPathRelativeToRoot(),
                            'name' => $media->name.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION),
                            'order_column' => ($document->attachments()->max('order_column') ?? 0) + 1,
                        ]);
                        $media->update(['model_type' => $attachment->getMorphClass(), 'model_id' => $attachment->id]);
                    }
                    ResidentDocumentUpload::whereKey($upload->id)->delete();
                }
                $workflow->record($document, $resubmitting ? 'Resident resubmitted application' : 'Resident submitted application', null, null, [
                    'purpose' => $document->purpose, 'application_kind' => $document->application_kind,
                    'reservations' => $document->reservations()->get()->toArray(),
                    'attachment_ids' => $document->attachments()->pluck('id')->all(),
                ]);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($stored);
            throw $exception;
        }

        return redirect()->route('resident.documents.show', $document)->with('status', 'Your application has been submitted for review.');
    }

    public function respond(Request $request, DocumentRequest $document, DocumentWorkflow $workflow): RedirectResponse
    {
        $this->owned($request, $document);
        $data = $request->validate(['action' => ['required', 'in:accept,revise,cancel']]);
        $workflow->residentAction($document, $data['action']);

        return redirect()->route('resident.documents.show', $document)->with('status', 'Your response has been recorded.');
    }

    public function attachment(Request $request, RequestAttachment $attachment): StreamedResponse
    {
        $this->owned($request, $attachment->documentRequest);

        return $this->download($attachment);
    }

    public function staffAttachment(Request $request, RequestAttachment $attachment): StreamedResponse
    {
        abort_unless($request->user('employee')?->is_active, 403);
        Gate::forUser($request->user('employee'))->authorize('view', $attachment->documentRequest);

        return $this->download($attachment);
    }

    private function download(RequestAttachment $attachment): StreamedResponse
    {
        abort_unless(Storage::disk('private')->exists($attachment->filePath()), 404);

        return Storage::disk('private')->download($attachment->filePath(), $attachment->name, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    private function owned(Request $request, DocumentRequest $document): void
    {
        abort_unless($document->resident_id === $request->user('resident')->id, 404);
    }
}
