<?php

namespace App\Filament\Resources\DocumentRequests\Schemas;

use App\DocumentWorkflow;
use App\Enums\RequestStatus;
use App\Models\DocumentRequest;
use App\Models\Resident;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class DocumentRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Application')->icon('heroicon-o-document-text')
                ->schema([
                    Select::make('resident_id')->label('Resident')->relationship('resident', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn (Resident $record): string => $record->full_name)->disabled()->required(),
                    Select::make('document_type_id')->label('Document')->relationship('documentType', 'document_type_name')->disabled()->required(),
                    Textarea::make('purpose')->disabled()->rows(4)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Review & next step')->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Select::make('status')->label('Next step')
                        ->options(fn (DocumentRequest $record): array => app(DocumentWorkflow::class)->transitions($record))->required()->live(),
                    Textarea::make('review_note')->label('Message to resident')->maxLength(5000)->rows(3)->columnSpanFull()
                        ->helperText('Add any corrections or instructions.'),
                    Checkbox::make('rbi_verified')->label('RBI residency verified: at least 8 months')
                        ->visible(fn (DocumentRequest $record, Get $get): bool => $record->workflowCategory() === 'certification' && $record->status === RequestStatus::Pending && $get('status') === 'processing'),
                    Section::make('Office payment')->schema([
                        TextInput::make('official_receipt')->label('Official Receipt number')->maxLength(100),
                        TextInput::make('amount_paid')->numeric()->minValue(0)->prefix('₱')->label('Amount received'),
                    ])->columns(2)->columnSpanFull()
                        ->visible(fn (DocumentRequest $record, Get $get): bool => $record->status === RequestStatus::AwaitingPayment && $get('status') === 'processing'),
                ])->columnSpanFull(),
            Section::make('Street & equipment allocation')->icon('heroicon-o-truck')
                ->schema([
                    Repeater::make('allocations')->label('Set 0 for an unavailable item')
                        ->schema([Hidden::make('id'), TextInput::make('name')->disabled(), TextInput::make('requested_quantity')->disabled(), TextInput::make('approved_quantity')->integer()->minValue(0)->required()])
                        ->addable(false)->deletable(false)->reorderable(false)->columns(3),
                ])->columnSpanFull()
                ->visible(fn (DocumentRequest $record, Get $get): bool => $record->workflowCategory() === 'use_closure' && in_array($get('status'), ['approved', 'awaiting_confirmation'], true) && $record->status === RequestStatus::Pending),
            Section::make('Office record & history')->icon('heroicon-o-clock')->collapsible()
                ->schema([View::make('filament.requests.summary')])->columnSpanFull(),
        ]);
    }
}
