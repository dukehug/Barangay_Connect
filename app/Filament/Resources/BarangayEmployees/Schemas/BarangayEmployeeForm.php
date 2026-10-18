<?php

namespace App\Filament\Resources\BarangayEmployees\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BarangayEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('role')
                    ->required()
                    ->default('staff'),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('middle_name'),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->unique(ignoreRecord: true)
                    ->email()
                    ->required(),
                TextInput::make('contact_number')
                    ->tel(),
                TextInput::make('password')
                    ->password()
                    ->required(),
            ]);
    }
}
