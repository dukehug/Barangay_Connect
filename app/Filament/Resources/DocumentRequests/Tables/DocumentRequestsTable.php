<?php

namespace App\Filament\Resources\DocumentRequests\Tables;

use App\Enums\RequestStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resident.full_name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('documentType.document_type_name')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('staff.first_name')->placeholder('Unassigned'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(RequestStatus::class),
                Filter::make('unassigned')->label('Unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('staff_id')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
