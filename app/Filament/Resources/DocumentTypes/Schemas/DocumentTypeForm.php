<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('document_type_name')
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('document_type_description'),
                Select::make('category')->label('Workflow')->options(['clearance' => 'Barangay Clearance', 'certification' => 'Barangay Certification', 'use_closure' => 'Use / Closure'])->default('certification')->required(),
                TextInput::make('document_type_fee')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_required_other_documents')
                    ->required(),
                TextInput::make('processing_days')
                    ->numeric(),
            ]);
    }
}
