<?php

namespace App\Filament\Resources\ResourceItems\Pages;

use App\Filament\Resources\ResourceItems\ResourceItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageResourceItems extends ManageRecords
{
    protected static string $resource = ResourceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
