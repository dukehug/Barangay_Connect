<?php

namespace App\Filament\Resources\DocumentRequests\RelationManagers;

use App\DocumentWorkflow;
use App\Models\RequestAttachment;
use App\Models\WorkflowSetting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Joaopaulolndev\FilamentPdfViewer\Infolists\Components\PdfViewerEntry;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kind')->label('Document type')->options(RequestAttachment::KINDS)->required(),
            SpatieMediaLibraryFileUpload::make('file')->label('File')
                ->collection('document')->disk('private')->visibility('private')
                ->maxSize(10240)->acceptedFileTypes(RequestAttachment::MIME_TYPES)
                ->required()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('media'))
            ->defaultSort('order_column')->reorderable('order_column')
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->columns([
                Stack::make([
                    ViewColumn::make('preview')->view('filament.requests.attachment-preview'),
                    TextColumn::make('name')->wrap()->searchable()->weight('medium'),
                    TextColumn::make('kind')->formatStateUsing(fn (string $state): string => RequestAttachment::KINDS[$state] ?? $state)->color('gray'),
                ])->space(3),
            ])
            ->headerActions([
                CreateAction::make()->label('Add file')->databaseTransaction()
                    ->visible(fn (): bool => $this->getOwnerRecord()->fresh()->isEditable())
                    ->before(function (): void {
                        WorkflowSetting::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                        abort_unless(Filament::auth()->user()->is_active && $this->getOwnerRecord()->fresh()->isEditable(), 403);
                    })
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'path' => '', 'name' => 'Document', 'order_column' => ($this->getOwnerRecord()->attachments()->max('order_column') ?? 0) + 1])
                    ->after(function (RequestAttachment $record): void {
                        $media = $record->load('media')->getFirstMedia('document');
                        $record->update(['name' => $media->name.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION), 'path' => $media->getPathRelativeToRoot()]);
                        app(DocumentWorkflow::class)->record($record->documentRequest, 'Staff added attachment', null, Filament::auth()->id(), ['attachment_id' => $record->id]);
                    }),
            ])
            ->recordActions([
                Action::make('preview')->label('Preview')->icon('heroicon-o-eye')->authorize('view')
                    ->modalHeading(fn (RequestAttachment $record): string => $record->name)
                    ->modalSubmitAction(false)->modalCancelActionLabel('Close')->modalWidth('5xl')
                    ->schema([
                        PdfViewerEntry::make('pdf_preview')->label('PDF')->state(null)
                            ->fileUrl(fn (RequestAttachment $record): string => $record->previewUrl())
                            ->disk('private')->visibility('private')->minHeight('70svh')
                            ->visible(fn (RequestAttachment $record): bool => $record->mimeType() === 'application/pdf'),
                        ImageEntry::make('image_preview')->hiddenLabel()->state(fn (RequestAttachment $record): string => $record->previewUrl())
                            ->imageHeight('auto')->imageWidth('100%')
                            ->visible(fn (RequestAttachment $record): bool => $record->isImage()),
                    ]),
                Action::make('download')->label('Download')->icon('heroicon-o-arrow-down-tray')->authorize('view')
                    ->url(fn (RequestAttachment $record): string => route('admin.requests.attachment', $record)),
                DeleteAction::make()->using(function (RequestAttachment $record): bool {
                    return app(DocumentWorkflow::class)->locked(function () use ($record): bool {
                        $record->refresh();
                        Gate::forUser(Filament::auth()->user())->authorize('delete', $record);
                        app(DocumentWorkflow::class)->record($record->documentRequest, 'Staff deleted attachment', null, Filament::auth()->id(), ['attachment_id' => $record->id, 'name' => $record->name]);
                        $legacyPath = $record->getFirstMedia('document')?->getCustomProperty('legacy_private_path')
                ?? ($record->hasMedia('document') ? null : $record->path);
                        $deleted = $record->delete();
                        if ($legacyPath) {
                            Storage::disk('private')->delete($legacyPath);
                        }

                        return $deleted;
                    });
                }),
            ]);
    }
}
