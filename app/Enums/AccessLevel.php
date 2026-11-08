<?php

namespace App\Enums;

enum AccessLevel: string
{
    case LOCAL = 'local';
    case REGIONAL = 'regional';
    case NATIONAL = 'national';

    // ABC enrolments and nothing else: no church, region or site content
    // (owner request, 2026-11-02). See AbcEnrolmentPolicy.
    case BIBLE_SCHOOL = 'bible_school';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOCAL => 'Local (single church)',
            self::REGIONAL => 'Regional (one region)',
            self::NATIONAL => 'National (all regions)',
            self::BIBLE_SCHOOL => 'Bible School (ABC enrolments only)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOCAL => 'gray',
            self::REGIONAL => 'info',
            self::NATIONAL => 'success',
            self::BIBLE_SCHOOL => 'warning',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $l) => [$l->value => $l->getLabel()])
            ->toArray();
    }
}
