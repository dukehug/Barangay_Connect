<?php

namespace App\Filament\Widgets;

use App\Models\DocumentRequest;
use Filament\Widgets\ChartWidget;

class RequestsChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Document requests — last 6 months';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $currentMonth = now()->startOfMonth();
        $labels = [];
        $counts = [];
        $columnSpan = 12;

        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $start = $currentMonth->copy()->subMonths($monthsAgo);
            $labels[] = $start->format('M Y');
            $counts[] = DocumentRequest::where('created_at', '>=', $start)
                ->where('created_at', '<', $start->copy()->addMonth())
                ->count();
        }

        return [
            'datasets' => [
                ['label' => 'Document requests', 'data' => $counts, 'backgroundColor' => '#3b82f6'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
