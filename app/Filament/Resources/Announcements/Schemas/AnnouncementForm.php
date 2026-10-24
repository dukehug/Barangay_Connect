<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('title')
                    ->maxLength(200)
                    ->required(),

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


                RichEditor::make('content')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'link'],
                        ['h2', 'h3'],
                        ['alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'bulletList', 'orderedList'],
                        ['table', 'attachFiles'],
                        ['undo', 'redo'],
                    ])

                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('announcements')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsAcceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->fileAttachmentsMaxSize(5120)
                    ->preventFileAttachmentPathTampering()
                    ->required()
                    ->columnSpanFull(),
                
                DatePicker::make('date_tested')
                    ->label('Date Posted')
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->displayFormat('d/m/Y'),
            ]);
    }
}
