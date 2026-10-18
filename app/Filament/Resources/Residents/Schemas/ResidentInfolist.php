<?php

namespace App\Filament\Resources\Residents\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ResidentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('full_name')
                    ->weight(FontWeight::Bold)
                    ->label('Full Name')
                    ->getStateUsing(fn ($record) => $record->first_name.' '.($record->middle_name ? $record->middle_name.' ' : '').$record->last_name),
                TextEntry::make('email')
                    ->weight(FontWeight::Bold)
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->weight(FontWeight::Bold)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('first_name')
                    ->weight(FontWeight::Bold),
                TextEntry::make('middle_name')
                    ->weight(FontWeight::Bold)
                    ->placeholder('-'),
                TextEntry::make('last_name')
                    ->weight(FontWeight::Bold),
                TextEntry::make('gender')
                    ->weight(FontWeight::Bold),
                TextEntry::make('birth_date')
                    ->weight(FontWeight::Bold)
                    ->date(),
                TextEntry::make('marital_status')
                    ->weight(FontWeight::Bold),
                TextEntry::make('blood_type')
                    ->weight(FontWeight::Bold)
                    ->placeholder('-'),
                TextEntry::make('contact_number')
                    ->weight(FontWeight::Bold)
                    ->placeholder('-'),
                TextEntry::make('resident_address')
                    ->weight(FontWeight::Bold)
                    ->placeholder('-'),
                ImageEntry::make('profile_image_url')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->weight(FontWeight::Bold)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->weight(FontWeight::Bold)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
