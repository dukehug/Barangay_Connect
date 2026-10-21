<?php

namespace App\Filament\Widgets;

use App\Models\Complaint;
use Filament\Widgets\ChartWidget;

class ComplaintsTypesPieChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Complaints by type';

    protected ?string $description = 'All complaints · All statuses · All time';

    protected function getData(): array
    {
        $typeName = "COALESCE(complaint_types.name, NULLIF(TRIM(complaints.complaint_type), ''), 'Uncategorized')";
        $counts = Complaint::query()
            ->leftJoin('complaint_types', 'complaints.complaint_type_id', '=', 'complaint_types.id')
            ->selectRaw($typeName.' AS type_name, COUNT(*) AS total')
            ->groupByRaw($typeName)
            ->orderByDesc('total')
            ->orderBy('type_name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Complaints',
                    'data' => $counts->map(fn (Complaint $count): int => (int) $count->total)->all(),
                    'backgroundColor' => $counts->values()->map(fn (Complaint $count, int $index): string => 'hsl('.(($index * 137) % 360).', 65%, 58%)')->all(),
                ],
            ],
            'labels' => $counts->pluck('type_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
