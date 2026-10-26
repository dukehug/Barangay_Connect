<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class EditComplaint extends EditRecord
{
    protected static string $resource = ComplaintResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function hasFormWrapper(): bool
    {
        return false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('form')]);
    }

    public function getTitle(): string
    {
        return $this->record->reference.' · '.($this->record->resident?->full_name ?? 'Unknown resident');
    }

    public function getBreadcrumb(): string
    {
        return 'Handle';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function previewAttachmentAction(): Action
    {
        return Action::make('previewAttachment')
            ->label('Preview')->icon('heroicon-o-eye')
            ->authorize(fn (): bool => Filament::auth()->user()->is_active && Gate::forUser(Filament::auth()->user())->allows('view', $this->record))
            ->modalHeading('File preview')
            ->modalSubmitAction(false)->modalCancelActionLabel('Close')->modalWidth('5xl')
            ->modalContent(function (array $arguments): View {
                if (isset($arguments['media'])) {
                    $media = $this->record->media()->where('collection_name', 'document')->findOrFail($arguments['media']);
                    $url = Storage::disk('private')->temporaryUrl($media->getPathRelativeToRoot(), now()->addMinutes(30));
                    $name = $media->file_name;
                    $isImage = str_starts_with($media->mime_type, 'image/');
                } else {
                    abort_unless($this->record->attachment_url, 404);
                    $url = route('admin.complaints.attachment', $this->record);
                    $name = basename($this->record->attachment_url);
                    $isImage = true;
                }

                return view('filament.attachment-preview', compact('url', 'name', 'isImage'));
            });
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] instanceof ComplaintStatus
            ? $data['status']
            : ComplaintStatus::from($data['status']);

        $message = trim($data['review_note'] ?? '');
        $data = ['status' => $status, 'review_note' => $message === '' ? null : $message];

        if ($this->record->staff_id === null && $status !== ComplaintStatus::Pending) {
            $data['staff_id'] = Filament::auth()->id();
        }

        return $data;
    }
}
