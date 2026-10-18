<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->relationship('staff', 'id')
                    ->required(),
                TextInput::make('action')
                    ->required(),
                TextInput::make('table_affected')
                    ->required(),
                TextInput::make('record_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
