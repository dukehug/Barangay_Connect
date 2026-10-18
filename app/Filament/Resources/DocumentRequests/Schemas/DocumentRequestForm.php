<?php

namespace App\Filament\Resources\DocumentRequests\Schemas;

use App\Enums\RequestStatus;
use App\Models\Resident;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DocumentRequestForm
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
                Select::make('document_type_id')
                    ->relationship('documentType', 'document_type_name')
                    ->disabled()
                    ->required(),
                Textarea::make('purpose')
                    ->required()
                    ->disabled()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(RequestStatus::class)
                    ->default('pending')
                    ->required(),
            ]);
    }
}
