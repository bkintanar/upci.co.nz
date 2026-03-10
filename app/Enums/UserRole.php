<?php

namespace App\Enums;

enum UserRole: string
{
    case MEMBER = 'member';
    case USHER = 'usher';
    case DEACON = 'deacon';
    case ELDER = 'elder';
    case ASSISTANT_PASTOR = 'assistant_pastor';
    case PASTOR = 'pastor';
    case SENIOR_PASTOR = 'senior_pastor';
    case ADMINISTRATOR = 'administrator';

    public function getLabel(): string
    {
        return match ($this) {
            self::MEMBER => 'Member',
            self::USHER => 'Usher',
            self::DEACON => 'Deacon',
            self::ELDER => 'Elder',
            self::ASSISTANT_PASTOR => 'Assistant Pastor',
            self::PASTOR => 'Pastor',
            self::SENIOR_PASTOR => 'Senior Pastor',
            self::ADMINISTRATOR => 'Administrator',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MEMBER => 'gray',
            self::USHER => 'blue',
            self::DEACON => 'green',
            self::ELDER => 'yellow',
            self::ASSISTANT_PASTOR => 'orange',
            self::PASTOR => 'red',
            self::SENIOR_PASTOR => 'purple',
            self::ADMINISTRATOR => 'indigo',
        };
    }

    public function getLevel(): int
    {
        return match ($this) {
            self::MEMBER => 1,
            self::USHER => 2,
            self::DEACON => 3,
            self::ELDER => 4,
            self::ASSISTANT_PASTOR => 5,
            self::PASTOR => 6,
            self::SENIOR_PASTOR => 7,
            self::ADMINISTRATOR => 8,
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->getLabel()])
            ->toArray();
    }
}
