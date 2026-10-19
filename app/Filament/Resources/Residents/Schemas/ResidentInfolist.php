<?php

namespace App\Filament\Resources\Residents\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class ResidentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Chunk 1: 核心身份 - 用戶第一眼要確認是誰
                Section::make('Personal Identity')
                    ->description('Basic identification of the resident.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        ImageEntry::make('profile_image_url')
                            ->label('Profile Photo')
                            ->circular()
                            ->defaultImageUrl(url('/profile-images/default-avatar.png')),

                        Grid::make(2)->schema([
                            TextEntry::make('full_name')
                                ->label('Full Name')
                                ->weight(FontWeight::Bold)
                                ->size(TextSize::Large)
                                ->getStateUsing(fn ($record) => $record->first_name.' '.($record->middle_name ? $record->middle_name.' ' : '').$record->last_name)
                                ->columnSpanFull(),
                            TextEntry::make('first_name'),
                            TextEntry::make('middle_name')->placeholder('None'),
                            TextEntry::make('last_name'),
                        ]),
                    ])
                    ->columns(3),


                // Chunk 2: 聯絡方式 - 另一種性質的資訊
                Section::make('Contact Information')
                    ->description('How to contact the resident.')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextEntry::make('contact_number')->placeholder('-')->icon('heroicon-o-device-phone-mobile')->copyable(),
                        TextEntry::make('email')->label('Email address')->icon('heroicon-o-envelope')->copyable(),
                        TextEntry::make('email_verified_at')->dateTime()->label('Verified At')->placeholder('Not verified'),
                        TextEntry::make('resident_address')->columnSpanFull()->icon('heroicon-o-map-pin')->placeholder('-'),
                    ])
                    ->columns(2),

                // Chunk 3: 人口統計 - 歸類為一組
                Section::make('Demographic Information')
                    ->description('Gender, birth, and personal details.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        TextEntry::make('gender')->badge()->color(fn($state) => $state === 'male' ? 'info' : 'pink'),
                        TextEntry::make('birth_date')->date()->icon('heroicon-o-calendar'),
                        TextEntry::make('marital_status')->badge(),
                        TextEntry::make('blood_type')->placeholder('-')->badge()->color('danger'),
                    ])
                    ->columns(4),

                

                // Chunk 4: 系統資訊 - 次要資訊，放最後並且收合、灰色化
                Section::make('System Metadata')
                    ->description('Internal records.')
                    ->icon('heroicon-o-clock')
                    //->collapsible()
                    //->collapsed()
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ])
                    ->columns(2)
                    ->extraAttributes(['class' => 'opacity-75']),
            ]);
    }
}