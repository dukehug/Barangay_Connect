<?php

namespace App\Filament\Resources\ComplaintTypes\Pages;

use App\Filament\Resources\ComplaintTypes\ComplaintTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageComplaintTypes extends ManageRecords
{
    protected static string $resource = ComplaintTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
