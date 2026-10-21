<?php

namespace App\Livewire;

use App\DocumentWorkflow;
use App\Models\RequestAttachment;
use App\Models\ResidentDocumentUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResidentDocumentUploader extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    #[Locked]
    public ?string $token = null;

    #[Locked]
    public string $kind;

    #[Locked]
    public bool $hasUpload = false;

    /** @var array<string, mixed> */
    public array $data = [];

    private bool $savingUpload = false;

    public function mount(string $kind, ?string $token = null): void
    {
        abort_unless(auth('resident')->check() && (array_key_exists($kind, RequestAttachment::KINDS) || $kind === 'complaint_attachment'), 403);
        $this->kind = $kind;
        $upload = $token ? ResidentDocumentUpload::where('token', $token)->where('resident_id', auth('resident')->id())
            ->where('kind', $kind)->where('created_at', '>=', now()->subDay())->first() : null;
        $upload ??= ResidentDocumentUpload::create(['resident_id' => auth('resident')->id(), 'kind' => $kind]);
        $this->token = $upload->token;
        $this->hasUpload = $upload->hasMedia('document');
        $this->form->fill();
    }

    public function hydrate(): void
    {
        $this->ownedUpload();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            SpatieMediaLibraryFileUpload::make('file')->id('resident-upload-'.$this->kind)->hiddenLabel()
                ->collection('document')->disk('private')->visibility('private')
                ->multiple()->acceptedFileTypes(RequestAttachment::MIME_TYPES)->maxSize(10240)
                ->imagePreviewHeight('140')->live()
                ->afterStateUpdated(fn () => $this->saveUpload()),
        ])->statePath('data')->model($this->ownedUpload());
    }

    public function saveUpload(): void
    {
        if ($this->savingUpload) {
            return;
        }
        $this->savingUpload = true;
        try {
            app(DocumentWorkflow::class)->locked(function (): void {
                $upload = $this->ownedUpload();
                $this->form->model($upload)->validate();
                $this->form->saveRelationships();
                $upload->load('media');
                $this->hasUpload = $this->ownedUpload()->hasMedia('document');
            });
        } finally {
            $this->savingUpload = false;
        }
    }

    private function ownedUpload(): ResidentDocumentUpload
    {
        abort_unless(auth('resident')->check(), 403);

        return ResidentDocumentUpload::where('token', $this->token)->where('kind', $this->kind)
            ->where('resident_id', auth('resident')->id())->where('created_at', '>=', now()->subDay())->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.resident-document-uploader');
    }
}
