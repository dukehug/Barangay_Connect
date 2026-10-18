<?php

namespace App\Filament\Resources\BarangayEmployees\Pages;

use App\Filament\Resources\BarangayEmployees\BarangayEmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBarangayEmployee extends EditRecord
{
    protected static string $resource = BarangayEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
