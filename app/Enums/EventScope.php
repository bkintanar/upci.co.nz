<?php

namespace App\Enums;

/**
 * Separates the national calendar from regional and departmental events
 * (requirement 9), which until now were one undifferentiated list.
 *
 * Region was previously expressible only as free text inside an event's NAME
 * — "PM – Central Region, Waikato" — which no query can filter on. This makes
 * it structural.
 */
enum EventScope: string
{
    case NATIONAL = 'national';
    case REGIONAL = 'regional';
    case DEPARTMENT = 'department';

    public function label(): string
    {
        return match ($this) {
            self::NATIONAL => 'National Calendar',
            self::REGIONAL => 'Regional Event',
            self::DEPARTMENT => 'Department Event',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $scope) => [$scope->value => $scope->label()])
            ->all();
    }
}
