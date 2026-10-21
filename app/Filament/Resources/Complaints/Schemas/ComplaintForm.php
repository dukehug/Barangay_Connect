<?php

namespace App\Filament\Resources\Complaints\Schemas;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\RequestAttachment;
use App\Models\Resident;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Complaint details')->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    Select::make('resident_id')->label('Resident')->relationship('resident', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn (Resident $record): string => $record->full_name)->disabled()->required(),
                    TextInput::make('complaint_type')->label('Category')->disabled()->required(),
                    Textarea::make('content')->label('What happened')->disabled()->required()->rows(6)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Handle complaint')->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Select::make('status')->label('Status')->options(ComplaintStatus::class)->default('pending')->required(),
                ])->columnSpanFull(),
            Section::make('Attachments')->icon('heroicon-o-photo')->collapsible()
                ->schema([
                    SpatieMediaLibraryFileUpload::make('document')->multiple()->hiddenLabel()->collection('document')
                        ->disk('private')->visibility('private')->maxSize(10240)->acceptedFileTypes(RequestAttachment::MIME_TYPES)
                        ->disabled()->openable()->downloadable()->visible(fn (?Complaint $record): bool => $record?->hasMedia('document') ?? false),
                    FileUpload::make('attachment_url')->visible(fn (?Complaint $record): bool => filled($record?->attachment_url))->hiddenLabel()
                        ->disk(fn (?Complaint $record): string => $record?->attachment_url && Storage::disk('local')->exists($record->attachment_url) ? 'local' : 'public')
                        ->visibility('private')->maxSize(5120)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disabled()->openable()->downloadable(),
                ])->visible(fn (?Complaint $record): bool => filled($record?->attachment_url) || ($record?->hasMedia('document') ?? false))->columnSpanFull(),
        ]);
    }
}
