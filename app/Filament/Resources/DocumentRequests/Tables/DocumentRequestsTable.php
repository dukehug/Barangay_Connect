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
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('Request ID')->formatStateUsing(fn (int $state): string => 'DOC-'.$state)->sortable(),
                TextColumn::make('resident.full_name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('documentType.document_type_name')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('confirmation_expires_at')->label('Confirm by')->dateTime(timezone: 'Asia/Manila')->sortable(),
                TextColumn::make('staff.first_name')->placeholder('Unassigned'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(RequestStatus::class),
                Filter::make('overdue_equipment')->label('Overdue equipment')->query(fn (Builder $query): Builder => $query->whereHas('reservations', fn (Builder $lines): Builder => $lines->where('ends_at', '<', now())->whereColumn('issued_quantity', '>', 'returned_quantity'))),
                Filter::make('unassigned')->label('Unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('staff_id')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->recordActionsColumnLabel('Actions');
    }
}
