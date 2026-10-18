<?php

namespace App\Filament\Resources\DocumentRequests\Pages;

use App\Enums\RequestStatus;
use App\Filament\Resources\DocumentRequests\DocumentRequestResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditDocumentRequest extends EditRecord
{
    protected static string $resource = DocumentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] instanceof RequestStatus
            ? $data['status']
            : RequestStatus::from($data['status']);

        $data = ['status' => $status];

        if ($this->record->staff_id === null && $status !== RequestStatus::Pending) {
            $data['staff_id'] = Filament::auth()->id();
        }

        return $data;
    }
}
