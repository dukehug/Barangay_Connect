<?php

namespace App\Http\Controllers;

use App\DocumentWorkflow;
use App\Enums\ComplaintStatus;
use App\Enums\RequestStatus;
use App\Http\Requests\ResidentComplaintRequest;
use App\Http\Requests\ResidentProfileRequest;
use App\Models\Announcement;
use App\Models\ComplaintType;
use App\Models\DocumentType;
use App\Models\ResidentDocumentUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResidentPortalController extends Controller
{
    private function publishedAnnouncements(): Builder
    {
        return Announcement::query()->where('published', true)->whereNotNull('posted_at')->where('posted_at', '<=', now());
    }

    public function dashboard(): View
    {
        return view('resident.dashboard', ['announcements' => $this->publishedAnnouncements()->latest('posted_at')->limit(3)->get()]);
    }

    public function announcements(Request $request): View
    {
        $filters = $request->validate(['category' => ['nullable', 'string', 'max:255']]);

        return view('resident.announcements', [
            'categories' => $this->publishedAnnouncements()->distinct()->pluck('category'),
            'announcements' => $this->publishedAnnouncements()->when($filters['category'] ?? null, fn (Builder $query, string $category): Builder => $query->where('category', $category))->latest('posted_at')->orderByDesc('id')->paginate(10)->withQueryString(),
        ]);
    }

    public function announcement(Announcement $announcement): View
    {
        abort_unless($this->publishedAnnouncements()->whereKey($announcement->id)->exists(), 404);

        $ordered = $this->publishedAnnouncements()->orderByDesc('posted_at')->orderByDesc('id')->pluck('id');
        $position = $ordered->search($announcement->id);

        return view('resident.announcement', [
            'announcement' => $announcement,
            'previousAnnouncement' => $position > 0 ? $ordered[$position - 1] : null,
            'nextAnnouncement' => $ordered->get($position + 1),
        ]);
    }

    public function documents(Request $request): View
    {
        app(DocumentWorkflow::class)->expire();
        $filters = $this->recordFilters($request);
        $documents = $request->user('resident')->documentRequests()->with('documentType');
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $documents->where(function (Builder $query) use ($search): void {
                $query->where('purpose', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.strtoupper($search).'%')
                    ->orWhereHas('documentType', fn (Builder $type): Builder => $type->where('document_type_name', 'like', '%'.$search.'%'))
                    ->orWhereIn('status', $this->matchingStatuses(RequestStatus::cases(), $search));
            });
        }

        return view('resident.documents', [
            'documents' => $documents->latest()->orderByDesc('id')->paginate($filters['per_page'])->withQueryString(),
            'documentTypes' => DocumentType::query()->where('is_active', true)->orderBy('document_type_name')->get(),
        ]);
    }

    public function complaints(Request $request): View
    {
        $filters = $this->recordFilters($request);
        $complaints = $request->user('resident')->complaints()->with('media');
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $complaints->where(function (Builder $query) use ($search): void {
                $query->where('content', 'like', '%'.$search.'%')
                    ->orWhere('complaint_type', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.strtoupper($search).'%')
                    ->orWhereIn('status', $this->matchingStatuses(ComplaintStatus::cases(), $search));
            });
        }

        return view('resident.complaints', ['complaintTypes' => ComplaintType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(), 'complaints' => $complaints->latest()->orderByDesc('id')->paginate($filters['per_page'])->withQueryString()]);
    }

    public function showComplaint(Request $request, int $complaint): View
    {
        return view('resident.complaint-detail', ['complaint' => $request->user('resident')->complaints()->with('media')->findOrFail($complaint)]);
    }

    public function storeComplaint(ResidentComplaintRequest $request): RedirectResponse
    {
        $stored = null;
        try {
            app(DocumentWorkflow::class)->locked(function () use ($request, &$stored): void {
                $data = $request->safe()->except(['attachment', 'uploads']);
                $data['complaint_type_id'] = ComplaintType::where('name', $data['complaint_type'])->where('is_active', true)->firstOrFail()->id;
                $data['status'] = ComplaintStatus::Pending;
                $complaint = $request->user('resident')->complaints()->create($data);
                if ($token = $request->validated('uploads.complaint_attachment')) {
                    $upload = ResidentDocumentUpload::where('token', $token)->where('resident_id', $request->user('resident')->id)
                        ->where('kind', 'complaint_attachment')->where('created_at', '>=', now()->subDay())->lockForUpdate()->first();
                    $uploadedFiles = $upload?->getMedia('document');
                    if (! $uploadedFiles || $uploadedFiles->isEmpty()) {
                        throw ValidationException::withMessages(['uploads.complaint_attachment' => 'This upload expired. Please upload the file again.']);
                    }
                    foreach ($uploadedFiles as $media) {
                        $media->update(['model_type' => $complaint->getMorphClass(), 'model_id' => $complaint->id]);
                    }
                    ResidentDocumentUpload::whereKey($upload->id)->delete();
                } elseif ($request->hasFile('attachment')) {
                    $media = $complaint->addMedia($request->file('attachment'))->toMediaCollection('document', 'private');
                    $stored = $media->getPathRelativeToRoot();
                }
            });
        } catch (\Throwable $exception) {
            if ($stored) {
                Storage::disk('private')->delete($stored);
            }
            throw $exception;
        }

        return redirect()->route('resident.complaints')->with('status', 'Your complaint has been sent to the barangay office.');
    }

    public function attachment(Request $request, int $complaint): StreamedResponse
    {
        $record = $request->user('resident')->complaints()->findOrFail($complaint);
        if (! $record->attachment_url && ($media = $record->getFirstMedia('document'))) {
            return Storage::disk('private')->response($media->getPathRelativeToRoot(), $media->file_name, [
                'Content-Type' => $media->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
            ]);
        }
        abort_unless($record->attachment_url, 404);
        $disk = Storage::disk('local')->exists($record->attachment_url) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($record->attachment_url), 404);

        return Storage::disk($disk)->response($record->attachment_url);
    }

    public function activity(Request $request): View
    {
        $resident = $request->user('resident');
        $activity = $resident->documentRequests()->with('documentType')->latest('updated_at')->limit(10)->get()->toBase()
            ->merge($resident->complaints()->latest('updated_at')->limit(10)->get())
            ->sortByDesc('updated_at')->take(10);

        return view('resident.activity', compact('activity'));
    }

    public function profile(Request $request): View
    {
        return view('resident.profile', ['resident' => $request->user('resident')]);
    }

    public function updateProfile(ResidentProfileRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        if ($request->hasFile('photo')) {
            $data['profile_image_url'] = $request->file('photo')->store('resident-photos', 'local');
        }
        $request->user('resident')->update($data);

        return redirect()->route('resident.profile')->with('status', 'Your profile has been updated.');
    }

    /** @return array{search: string, per_page: int} */
    private function recordFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:2,5,10,25'],
        ]);

        return ['search' => trim($validated['search'] ?? ''), 'per_page' => (int) ($validated['per_page'] ?? 10)];
    }

    /**
     * @param  array<int, RequestStatus|ComplaintStatus>  $statuses
     * @return array<int, string>
     */
    private function matchingStatuses(array $statuses, string $search): array
    {
        return array_values(array_map(
            fn (RequestStatus|ComplaintStatus $status): string => $status->value,
            array_filter($statuses, fn (RequestStatus|ComplaintStatus $status): bool => str_contains(strtolower($status->getLabel()), strtolower($search))),
        ));
    }

    public function photo(Request $request): StreamedResponse
    {
        $path = $request->user('resident')->profile_image_url;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
