<?php

namespace App\Filament\Resources\ResourceItems;

use App\DocumentWorkflow;
use App\Filament\Resources\ResourceItems\Pages\ManageResourceItems;
use App\Models\ResourceItem;
use App\ResourceAvailability;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ResourceItemResource extends Resource
{
    protected static ?string $model = ResourceItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-files';

    protected static string|UnitEnum|null $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'Streets & Equipment';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255)->unique(ignoreRecord: true)->helperText('For streets, define one canonical road segment per record to detect overlapping requests.'),
            Select::make('kind')->options(['street' => 'Street / road segment', 'equipment' => 'Equipment'])->required()
                ->disabled(fn (?ResourceItem $record): bool => $record?->reservations()->exists() ?? false),
            TextInput::make('quantity')->label('Usable inventory quantity')->integer()->minValue(0)->maxValue(100000)->default(0)->required()->helperText('Street segments have a capacity of 1. Enter verified equipment stock.'),
            Toggle::make('is_active')->default(true),
            Textarea::make('description')->maxLength(5000)->helperText('For streets, describe exact boundaries. This is shown to residents.')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('kind')->badge(),
            TextColumn::make('quantity')->label('Total stock')->formatStateUsing(fn (ResourceItem $record): int => $record->kind === 'street' ? 1 : $record->quantity),
            IconColumn::make('is_active')->boolean(),
        ])->filters([SelectFilter::make('kind')->options(['street' => 'Street', 'equipment' => 'Equipment'])])
            ->recordActions([EditAction::make()->using(function (ResourceItem $record, array $data, EditAction $action): ResourceItem {
                return app(DocumentWorkflow::class)->locked(function () use ($record, $data, $action): ResourceItem {
                    $statePath = $action->getLivewire()->getSchema('mountedActionSchema'.$action->getNestingIndex())->getStatePath();
                    $record->refresh();
                    if ($record->reservations()->exists() && ($data['kind'] ?? $record->kind) !== $record->kind) {
                        throw ValidationException::withMessages([$statePath.'.kind' => 'A resource with application history cannot change its kind.']);
                    }
                    $lastEnd = $record->reservations()->max('ends_at');
                    $end = $lastEnd ? max(CarbonImmutable::parse($lastEnd), CarbonImmutable::now()->addSecond()) : CarbonImmutable::now()->addSecond();
                    $usage = app(ResourceAvailability::class)->check($record, CarbonImmutable::now(), $end);
                    $capacity = ($data['kind'] ?? $record->kind) === 'street' ? 1 : (int) $data['quantity'];
                    if ($usage['reserved'] > $capacity || (! $data['is_active'] && $usage['reserved'] > 0)) {
                        throw ValidationException::withMessages([$statePath.'.quantity' => 'Existing reservations or unreturned equipment require '.$usage['reserved'].' units. Resolve those commitments before reducing stock or disabling the item.']);
                    }
                    $record->update($data);

                    return $record;
                });
            })]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageResourceItems::route('/')];
    }
}
