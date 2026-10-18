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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BarangayEmployeeResource extends Resource
{
    protected static ?string $model = BarangayEmployee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
