<?php

namespace App\Filament\Resources\BarangayEmployees\Pages;

use App\Filament\Resources\BarangayEmployees\BarangayEmployeeResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditBarangayEmployee extends EditRecord
{
    protected static string $resource = BarangayEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->id === Filament::auth()->id()) {
            unset($data['role'], $data['is_active']);
        }

        return $data;
    }
}
