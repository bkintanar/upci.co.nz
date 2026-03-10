<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class MainStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $lastEvent = Attendance::latest('date')->first();
        $previousEvent = Attendance::latest('date')->skip(1)->first();

        if (! $lastEvent) {
            return [
                Stat::make('No Events Yet', 'Start Recording')
                    ->description('Add first record')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('warning')
                    ->icon('heroicon-o-calendar-days')
                    ->columnSpan(4),
            ];
        }

        $total = $lastEvent->total;
        $eventName = $lastEvent->event ?: 'Church Event';

        // Fix date calculation
        $now = now();
        $eventDate = $lastEvent->date->startOfDay();
        $isToday = $eventDate->isToday();
        $isYesterday = $eventDate->isYesterday();
        $daysDiff = abs(round($eventDate->diffInDays($now)));

        // Calculate trend if previous event exists
        $trend = null;
        $trendIcon = null;
        $trendColor = 'gray';

        if ($previousEvent) {
            $difference = $total - $previousEvent->total;
            if ($difference > 0) {
                $trend = "+{$difference} vs last";
                $trendIcon = 'heroicon-m-arrow-trending-up';
                $trendColor = 'success';
            } elseif ($difference < 0) {
                $trend = "{$difference} vs last";
                $trendIcon = 'heroicon-m-arrow-trending-down';
                $trendColor = 'danger';
            } else {
                $trend = 'Same as last';
                $trendIcon = 'heroicon-m-minus';
                $trendColor = 'gray';
            }
        }

        // Standardize date format for consistent height
        $dateDescription = match (true) {
            $isToday => 'Today',
            $isYesterday => 'Yesterday',
            $daysDiff === 0 => 'Today',
            $daysDiff <= 7 => $daysDiff.($daysDiff === 1 ? ' day ago' : ' days ago'),
            default => $lastEvent->date->format('M d')
        };

        return [
            Stat::make('Latest Event', $eventName)
                ->description($dateDescription)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->icon('heroicon-o-calendar')
                ->columnSpan(2),

            Stat::make('Total Attendance', $total)
                ->description($trend ?: 'Total attendees')
                ->descriptionIcon($trendIcon ?: 'heroicon-m-users')
                ->color($trendColor)
                ->icon('heroicon-o-users')
                ->columnSpan(2),
        ];
    }
}
