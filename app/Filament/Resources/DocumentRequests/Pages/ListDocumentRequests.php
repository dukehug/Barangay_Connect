<?php

namespace App\Filament\Resources\DocumentRequests\Pages;

use App\DocumentWorkflow;
use App\Filament\Resources\DocumentRequests\DocumentRequestResource;
use App\Models\WorkflowSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDocumentRequests extends ListRecords
{
    protected static string $resource = DocumentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('confirmationSettings')->label('Confirmation deadline settings')
            ->visible(fn (): bool => Filament::auth()->user()->isAdmin())
            ->fillForm(fn (): array => ['confirmation_hours' => WorkflowSetting::current()->confirmation_hours])
            ->schema([TextInput::make('confirmation_hours')->label('Weekday hours to confirm')->integer()->minValue(1)->maxValue(720)->required()->helperText('Default: 48 hours. Saturdays and Sundays are skipped in Philippine time. Changes affect new offers only.')])
            ->action(function (array $data): void {
                abort_unless(Filament::auth()->user()->isAdmin(), 403);
                app(DocumentWorkflow::class)->locked(fn () => WorkflowSetting::current()->update(['confirmation_hours' => $data['confirmation_hours']]));
                Notification::make()->success()->title('Confirmation period updated')->send();
            })];
    }
}
