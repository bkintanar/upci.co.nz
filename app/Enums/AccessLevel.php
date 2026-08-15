<?php

namespace App\Enums;

enum AccessLevel: string
{
    case LOCAL = 'local';
    case REGIONAL = 'regional';
    case NATIONAL = 'national';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOCAL => 'Local (single church)',
            self::REGIONAL => 'Regional (one region)',
            self::NATIONAL => 'National (all regions)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOCAL => 'gray',
            self::REGIONAL => 'info',
            self::NATIONAL => 'success',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $l) => [$l->value => $l->getLabel()])
            ->toArray();
    }
}
