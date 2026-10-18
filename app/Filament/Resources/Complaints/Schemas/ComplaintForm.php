<?php

namespace App\Filament\Resources\Complaints\Schemas;

use App\Enums\ComplaintStatus;
use App\Models\Resident;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                    ->disk('public')
                    ->visibility('public')
                    ->disabled()
                    ->openable()
                    ->downloadable(),
            ]);
    }
}
