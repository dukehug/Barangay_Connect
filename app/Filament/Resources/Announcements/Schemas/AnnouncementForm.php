<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Models\Announcement;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
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

                SpatieMediaLibraryFileUpload::make('cover')
                    ->label('Cover image')
                    ->collection('cover')
                    ->disk('public')
                    ->visibility('public')
                    ->image()
                    ->acceptedFileTypes(Announcement::COVER_MIME_TYPES)
                    ->maxSize(5120)
                    ->imageEditor()
                    ->imageEditorAspectRatios(['16:9'])
                    ->helperText('Optional. JPG, PNG or WebP, up to 5 MB. Recommended: 1200 × 675 pixels (16:9). Without a cover, announcement cards use the default image.')
                    ->columnSpanFull(),

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
            ]);
    }
}
