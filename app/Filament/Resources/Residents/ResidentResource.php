<?php

namespace App\Filament\Resources\Residents;

use App\Filament\Resources\Residents\Pages\ListResidents;
use App\Filament\Resources\Residents\Pages\ViewResident;
use App\Filament\Resources\Residents\Schemas\ResidentForm;
use App\Filament\Resources\Residents\Schemas\ResidentInfolist;
use App\Filament\Resources\Residents\Tables\ResidentsTable;
use App\Models\Resident;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ResidentResource extends Resource
{
    protected static ?string $model = Resident::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-users';

    protected static string|UnitEnum|null $navigationGroup = 'Resident Records';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'Resident Model';

    public static function form(Schema $schema): Schema
    {
        return ResidentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ResidentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResidentsTable::configure($table);
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
            'index' => ListResidents::route('/'),
            'view' => ViewResident::route('/{record}'),
        ];
    }
}
