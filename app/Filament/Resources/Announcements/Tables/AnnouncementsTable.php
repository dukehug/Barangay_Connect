<?php

namespace App\Filament\Resources\Announcements\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('Cover')
                    ->collection('cover')
                    ->visibility('public')
                    ->imageWidth(80)
                    ->imageHeight(45),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                IconColumn::make('published')
                    ->boolean(),
                TextColumn::make('posted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'General' => 'General',
                        'Emergency' => 'Emergency',
                        'Event' => 'Event',
                    ]),
                SelectFilter::make('published')
                    ->options([
                        true => 'Published',
                        false => 'Unpublished',
                    ]),
            ])
            ->recordActions([
                EditAction::make(), DeleteAction::make(),
            ])
            ->recordActionsColumnLabel('Actions');
    }
}
