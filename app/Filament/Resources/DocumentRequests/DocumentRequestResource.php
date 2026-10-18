<?php

namespace App\Filament\Resources\DocumentRequests;

use App\Filament\Resources\DocumentRequests\Pages\EditDocumentRequest;
use App\Filament\Resources\DocumentRequests\Pages\ListDocumentRequests;
use App\Filament\Resources\DocumentRequests\Schemas\DocumentRequestForm;
use App\Filament\Resources\DocumentRequests\Tables\DocumentRequestsTable;
use App\Models\DocumentRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocumentRequestResource extends Resource
{
    protected static ?string $model = DocumentRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return DocumentRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentRequests::route('/'),
            'edit' => EditDocumentRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->where('status', 'pending')->count();
    }
}
