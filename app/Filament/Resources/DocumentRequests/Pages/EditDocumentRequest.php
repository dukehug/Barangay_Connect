<?php

namespace App\Filament\Resources\DocumentRequests\Pages;

use App\DocumentWorkflow;
use App\Enums\RequestStatus;
use App\Filament\Resources\DocumentRequests\DocumentRequestResource;
use App\Models\RequestReservation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditDocumentRequest extends EditRecord
{
    protected static string $resource = DocumentRequestResource::class;

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
        return 'DOC-'.$this->record->id.' · '.$this->record->resident->full_name;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        app(DocumentWorkflow::class)->expire();
        $this->record->refresh();
        $data['status'] = $this->record->status->value;
        $data['allocations'] = $this->record->reservations()->with('resourceItem')->get()->map(fn (RequestReservation $line): array => [
            'id' => $line->id, 'name' => $line->resourceItem->name, 'requested_quantity' => $line->requested_quantity,
            'approved_quantity' => $line->requested_quantity,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(DocumentWorkflow::class)->review($record, $data, Filament::auth()->user());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => ['data.'.$key => $messages])->all());
        }
    }

    public function getRequestActions(): array
    {
        return [
            Action::make('printDocument')->label('Print document')->url(fn (): string => route('admin.requests.print', $this->record))->openUrlInNewTab()
                ->visible(fn (): bool => in_array($this->record->status, [RequestStatus::Processing, RequestStatus::AwaitingSignature, RequestStatus::Approved, RequestStatus::Ready, RequestStatus::Released], true)),
            $this->equipmentAction(false),
            $this->equipmentAction(true),
        ];
    }

    private function equipmentAction(bool $returning): Action
    {
        return Action::make($returning ? 'returnEquipment' : 'issueEquipment')
            ->label($returning ? 'Record return' : 'Record collection')
            ->visible(fn (): bool => $this->record->reservations()->whereHas('resourceItem', fn (Builder $query): Builder => $query->where('kind', 'equipment'))->exists())
            ->schema([
                Select::make('reservation_id')->label('Equipment')->required()->options(fn (): array => $this->record->reservations()->with('resourceItem')->whereHas('resourceItem', fn (Builder $query): Builder => $query->where('kind', 'equipment'))->get()->mapWithKeys(fn (RequestReservation $line): array => [$line->id => $line->resourceItem->name.' — allocated '.$line->approved_quantity.', collected '.$line->issued_quantity.', outstanding '.$line->outstanding()])->all()),
                TextInput::make('quantity')->label($returning ? 'Quantity returned now' : 'Quantity collected now')->integer()->minValue(1)->required(),
            ])
            ->action(function (array $data, Action $action) use ($returning): void {
                try {
                    app(DocumentWorkflow::class)->equipment($this->record, (int) $data['reservation_id'], (int) $data['quantity'], $returning, Filament::auth()->user());
                } catch (ValidationException $exception) {
                    $statePath = $action->getLivewire()->getSchema('mountedActionSchema'.$action->getNestingIndex())->getStatePath();
                    throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => [$statePath.'.'.$key => $messages])->all());
                }
                Notification::make()->success()->title('Equipment movement recorded')->send();
                $this->fillForm();
            });
    }
}
