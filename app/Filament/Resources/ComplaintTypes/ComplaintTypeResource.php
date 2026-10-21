<?php

namespace App\Filament\Resources\ComplaintTypes;

use App\Filament\Resources\ComplaintTypes\Pages\ManageComplaintTypes;
use App\Models\ComplaintType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ComplaintTypeResource extends Resource
{
    protected static ?string $model = ComplaintType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'System Administration';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Complaint type')->description('Active types appear in the resident form.')->schema([
                TextInput::make('name')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('sort_order')->label('Display order')->integer()->minValue(0)->default(0)->required(),
                Toggle::make('is_active')->label('Available to residents')->default(true),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            TextColumn::make('name')->searchable()->sortable(),
            IconColumn::make('is_active')->label('Active')->boolean(),
            TextColumn::make('sort_order')->label('Order')->sortable(),
            TextColumn::make('complaints_count')->counts('complaints')->label('Complaints'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageComplaintTypes::route('/')];
    }
}
