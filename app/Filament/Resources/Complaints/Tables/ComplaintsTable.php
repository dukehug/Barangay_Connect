<?php

namespace App\Filament\Resources\Complaints\Tables;

use App\Enums\ComplaintStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComplaintsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resident.full_name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('complaint_type')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('staff.first_name')->placeholder('Unassigned'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ComplaintStatus::class),
                Filter::make('unassigned')->label('Unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('staff_id')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
