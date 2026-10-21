<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditComplaint extends EditRecord
{
    protected static string $resource = ComplaintResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function hasFormWrapper(): bool
    {
        return false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('form')]);
    }

    public function getTitle(): string
    {
        return 'CMP-'.$this->record->id.' · '.($this->record->resident?->full_name ?? 'Unknown resident');
    }

    public function getBreadcrumb(): string
    {
        return 'Handle';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] instanceof ComplaintStatus
            ? $data['status']
            : ComplaintStatus::from($data['status']);

        $message = trim($data['review_note'] ?? '');
        $data = ['status' => $status, 'review_note' => $message === '' ? null : $message];

        if ($this->record->staff_id === null && $status !== ComplaintStatus::Pending) {
            $data['staff_id'] = Filament::auth()->id();
        }

        return $data;
    }
}
