<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditComplaint extends EditRecord
{
    protected static string $resource = ComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] instanceof ComplaintStatus
            ? $data['status']
            : ComplaintStatus::from($data['status']);

        $data = ['status' => $status];

        if ($this->record->staff_id === null && $status !== ComplaintStatus::Pending) {
            $data['staff_id'] = Filament::auth()->id();
        }

        return $data;
    }
}
