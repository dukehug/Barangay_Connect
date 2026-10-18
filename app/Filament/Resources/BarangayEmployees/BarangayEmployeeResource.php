<?php

namespace App\Filament\Resources\BarangayEmployees;

use App\Filament\Resources\BarangayEmployees\Pages\CreateBarangayEmployee;
use App\Filament\Resources\BarangayEmployees\Pages\EditBarangayEmployee;
use App\Filament\Resources\BarangayEmployees\Pages\ListBarangayEmployees;
use App\Filament\Resources\BarangayEmployees\Schemas\BarangayEmployeeForm;
use App\Filament\Resources\BarangayEmployees\Tables\BarangayEmployeesTable;
use App\Models\BarangayEmployee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BarangayEmployeeResource extends Resource
{
    protected static ?string $model = BarangayEmployee::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-user-round-cog';

    protected static string|UnitEnum|null $navigationGroup = 'System Administration';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'Barangay Employees model';

    public static function form(Schema $schema): Schema
    {
        return BarangayEmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BarangayEmployeesTable::configure($table);
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
            'index' => ListBarangayEmployees::route('/'),
            'create' => CreateBarangayEmployee::route('/create'),
            'edit' => EditBarangayEmployee::route('/{record}/edit'),
        ];
    }
}
