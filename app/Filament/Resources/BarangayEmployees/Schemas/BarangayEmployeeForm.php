<?php

namespace App\Filament\Resources\BarangayEmployees\Schemas;

use App\Enums\EmployeeRole;
use App\Models\BarangayEmployee;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BarangayEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role')
                    ->options(EmployeeRole::class)
                    ->required()
                    ->default(EmployeeRole::Staff)
                    ->disabled(fn (?BarangayEmployee $record): bool => $record?->id === Filament::auth()->id()),
                Toggle::make('is_active')
                    ->default(true)
                    ->disabled(fn (?BarangayEmployee $record): bool => $record?->id === Filament::auth()->id()),
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
                    ->afterStateHydrated(function (TextInput $component): void {
                        $component->state(null);
                    })
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
