<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class CategoryBreakdownWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $lastEvent = Attendance::latest('date')->first();

        if (! $lastEvent || $lastEvent->total === 0) {
            return [
                Stat::make('No Breakdown Available', 'Add attendance data')
                    ->description('Categories will appear here')
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('gray')
                    ->icon('heroicon-o-user-group'),
            ];
        }

        $total = $lastEvent->total;
        $categoryStats = [];

        if ($lastEvent->mens > 0) {
            $percentage = round(($lastEvent->mens / $total) * 100);
            $categoryStats[] = Stat::make('Men', $lastEvent->mens)
                ->description("{$percentage}% of total")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(Color::Blue)
                ->icon('heroicon-o-user')
                ->chart([
                    rand(10, 30), rand(15, 35), rand(20, 40), $lastEvent->mens,
                ]);
        }

        if ($lastEvent->ladies > 0) {
            $percentage = round(($lastEvent->ladies / $total) * 100);
            $categoryStats[] = Stat::make('Ladies', $lastEvent->ladies)
                ->description("{$percentage}% of total")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(Color::Pink)
                ->icon('heroicon-o-user')
                ->chart([
                    rand(10, 30), rand(15, 35), rand(20, 40), $lastEvent->ladies,
                ]);
        }

        if ($lastEvent->youth > 0) {
            $percentage = round(($lastEvent->youth / $total) * 100);
            $categoryStats[] = Stat::make('Youth', $lastEvent->youth)
                ->description("{$percentage}% of total")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(Color::Orange)
                ->icon('heroicon-o-user-group')
                ->chart([
                    rand(5, 20), rand(8, 25), rand(10, 30), $lastEvent->youth,
                ]);
        }

        if ($lastEvent->children > 0) {
            $percentage = round(($lastEvent->children / $total) * 100);
            $categoryStats[] = Stat::make('Children', $lastEvent->children)
                ->description("{$percentage}% of total")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(Color::Yellow)
                ->icon('heroicon-o-user-group')
                ->chart([
                    rand(3, 15), rand(5, 20), rand(7, 25), $lastEvent->children,
                ]);
        }

        if ($lastEvent->visitors > 0) {
            $percentage = round(($lastEvent->visitors / $total) * 100);
            $categoryStats[] = Stat::make('Visitors', $lastEvent->visitors)
                ->description("{$percentage}% of total")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(Color::Purple)
                ->icon('heroicon-o-users')
                ->chart([
                    rand(1, 10), rand(2, 15), rand(3, 20), $lastEvent->visitors,
                ]);
        }

        return $categoryStats;
    }
}
