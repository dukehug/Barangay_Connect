<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Filament\Resources\DocumentRequests\DocumentRequestResource;
use App\Models\DocumentRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestPendingRequests extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest pending requests')
            ->query(DocumentRequest::query()
                ->with(['resident', 'documentType'])
                ->where('status', RequestStatus::Pending)
                ->latest()->orderByDesc('id')->limit(5))
            ->columns([
                TextColumn::make('resident.full_name')->label('Resident'),
                TextColumn::make('documentType.document_type_name')->label('Document type'),
                TextColumn::make('created_at')->label('Submitted')->since(),
            ])
            ->paginated(false)
            ->poll(null)
            ->recordUrl(fn (DocumentRequest $record): string => DocumentRequestResource::getUrl('edit', ['record' => $record]));
    }
}
