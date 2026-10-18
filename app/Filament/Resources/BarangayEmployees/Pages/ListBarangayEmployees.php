<?php

namespace App\Filament\Resources\BarangayEmployees\Pages;

use App\Filament\Resources\BarangayEmployees\BarangayEmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBarangayEmployees extends ListRecords
{
    protected static string $resource = BarangayEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
