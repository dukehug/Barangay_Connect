<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('title')
                    ->maxLength(200)
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Select::make('category')
                    ->required()
                    ->options([
                        'General' => 'General',
                        'Emergency' => 'Emergency',
                        'Event' => 'Event',
                    ])
                    ->required(),
                Toggle::make('published')
                    ->default(false),
            ]);
    }
}
