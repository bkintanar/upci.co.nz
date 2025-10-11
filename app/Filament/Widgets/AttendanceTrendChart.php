<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Attendance::where('date', '>=', Carbon::now()->subDays(30))
            ->orderBy('date')
            ->get()
            ->map(function ($attendance) {
                return [
                    'date' => $attendance->date->format('M d'),
                    'mens' => $attendance->mens,
                    'ladies' => $attendance->ladies,
                    'youth' => $attendance->youth,
                    'children' => $attendance->children,
                    'visitors' => $attendance->visitors,
                    'total' => $attendance->total,
                ];
            });

        return [
            'datasets' => [
                [
                    'label' => 'Men',
                    'data' => $data->pluck('mens')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'fill' => 'origin',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Ladies',
                    'data' => $data->pluck('ladies')->toArray(),
                    'borderColor' => '#ec4899',
                    'backgroundColor' => 'rgba(236, 72, 153, 0.7)',
                    'fill' => '-1',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Youth',
                    'data' => $data->pluck('youth')->toArray(),
                    'borderColor' => '#f97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.7)',
                    'fill' => '-1',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Children',
                    'data' => $data->pluck('children')->toArray(),
                    'borderColor' => '#eab308',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.7)',
                    'fill' => '-1',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Visitors',
                    'data' => $data->pluck('visitors')->toArray(),
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.7)',
                    'fill' => '-1',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Cumulative Attendance by Category (Last 30 Days)',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'position' => 'nearest',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Attendance Count',
                    ],
                ],
                'x' => [
                    'display' => true,
                    'stacked' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Date',
                    ],
                ],
            ],
            'elements' => [
                'line' => [
                    'tension' => 0.4,
                ],
                'point' => [
                    'radius' => 3,
                    'hoverRadius' => 5,
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
