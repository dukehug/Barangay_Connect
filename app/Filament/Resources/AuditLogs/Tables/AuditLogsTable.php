<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('staff.first_name')->label('Staff')->searchable(),
                TextColumn::make('action')->badge(),
                TextColumn::make('table_affected')->searchable(),
                TextColumn::make('record_id')->numeric()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')->options([
                    'created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted',
                ]),
                SelectFilter::make('table_affected')->options([
                    'announcements' => 'Announcements',
                    'document_requests' => 'Document requests',
                    'complaints' => 'Complaints',
                    'document_types' => 'Document types',
                    'barangay_employees' => 'Employees',
                    'residents' => 'Residents',
                ]),
                SelectFilter::make('staff')->relationship('staff', 'first_name')->label('Staff'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
