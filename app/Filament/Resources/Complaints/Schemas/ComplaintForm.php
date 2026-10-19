<?php

namespace App\Filament\Resources\Complaints\Schemas;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Resident;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('resident_id')
                    ->relationship('resident', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Resident $record): string => $record->full_name)
                    ->disabled()
                    ->required(),
                TextInput::make('complaint_type')
                    ->disabled()
                    ->required(),
                Textarea::make('content')
                    ->disabled()
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(ComplaintStatus::class)
                    ->default('pending')
                    ->required(),
                FileUpload::make('attachment_url')
                    ->disk(fn (?Complaint $record): string => $record?->attachment_url && Storage::disk('local')->exists($record->attachment_url) ? 'local' : 'public')
                    ->visibility('private')
                    ->disabled()
                    ->openable()
                    ->downloadable(),
            ]);
    }
}
