<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class ComplaintsTypesPieChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Complaints Types Pie Chart';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Complaints Types',
                    'data' => [
                        10, // Replace with actual data for Type A
                        20, // Replace with actual data for Type B
                        30, // Replace with actual data for Type C
                    ],
                    'backgroundColor' => [
                        '#FF6384', // Color for Type A
                        '#36A2EB', // Color for Type B
                        '#FFCE56', // Color for Type C
                    ],
                ],
            ],
            'labels' => [
                'Type A',
                'Type B',
                'Type C',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
