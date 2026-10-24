<?php

namespace App\Filament\Resources\Complaints\Schemas;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\Pages\EditComplaint;
use App\Models\Complaint;
use App\Models\RequestAttachment;
use App\Models\Resident;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'xl' => 3])->components([

            Group::make([
                Section::make('Complaint details')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Select::make('resident_id')
                            ->label('Resident')
                            ->relationship('resident', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Resident $record): string => $record->full_name)
                            ->disabled()
                            ->required(),
                        TextInput::make('complaint_type')
                            ->label('Category')
                            ->disabled()
                            ->required(),
                        Textarea::make('content')
                            ->label('What happened')
                            ->disabled()
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                        TextEntry::make('location')
                            ->label('Location')
                            ->icon('heroicon-o-map-pin')
                            ->state(fn (?Complaint $record): ?string => $record?->googleMapsUrl() ? $record->latitude.', '.$record->longitude.' · Open in Google Maps' : null)
                            ->placeholder('No location provided')
                            ->url(fn (?Complaint $record): ?string => $record?->googleMapsUrl())
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ])->columns(2)->columnSpanFull(),

                Section::make('Attachments')->icon('heroicon-o-photo')->collapsible()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('document')->multiple()->hiddenLabel()->collection('document')
                            ->disk('private')
                            ->visibility('private')
                            ->maxSize(10240)
                            ->acceptedFileTypes(RequestAttachment::MIME_TYPES)
                            ->disabled()
                            ->openable()
                            ->downloadable()
                            ->visible(fn (?Complaint $record): bool => $record?->hasMedia('document') ?? false),
                        FileUpload::make('attachment_url')
                            ->visible(fn (?Complaint $record): bool => filled($record?->attachment_url))->hiddenLabel()
                            ->disk(fn (?Complaint $record): string => $record?->attachment_url && Storage::disk('local')->exists($record->attachment_url) ? 'local' : 'public')
                            ->visibility('private')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disabled()
                            ->openable()
                            ->downloadable(),
                        Text::make('No uploaded attachments.')->color('gray')
                            ->visible(fn (?Complaint $record): bool => blank($record?->attachment_url) && ! ($record?->hasMedia('document') ?? false)),
                    ])->columnSpanFull(),
                Section::make('Office record & history')->icon('heroicon-o-clock')->collapsible()
                    ->schema([View::make('filament.complaint-summary')]),
            ])->columnSpan(['default' => 1, 'xl' => 2]),

            Group::make([
                Section::make('Review & next step')->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Select::make('status')
                            ->label('Next step')
                            ->options(ComplaintStatus::class)
                            ->default('pending')
                            ->required(),
                        Textarea::make('review_note')->label('Message to resident')->maxLength(5000)->rows(3)
                            ->helperText('Explain the status or next steps. The resident can see this message in their complaint details.'),
                    ]),
                Group::make(fn (EditComplaint $livewire): array => [$livewire->getFormActionsContentComponent()]),
            ])->columnSpan(1),
        ]);
    }
}
