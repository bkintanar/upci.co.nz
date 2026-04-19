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
    case REGIONAL_PRESBYTER = 'regional_presbyter';
    case EXECUTIVE_BOARD = 'executive_board';
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
            self::REGIONAL_PRESBYTER => 'Regional Presbyter',
            self::EXECUTIVE_BOARD => 'Executive Board',
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
            self::REGIONAL_PRESBYTER => 'info',
            self::EXECUTIVE_BOARD => 'success',
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
            self::REGIONAL_PRESBYTER => 8,
            self::EXECUTIVE_BOARD => 9,
            self::ADMINISTRATOR => 10,
        };
    }

    public static function pastorRoles(): array
    {
        return [self::PASTOR, self::SENIOR_PASTOR, self::ASSISTANT_PASTOR];
    }

    public static function hasFullAccess(\App\Models\User $user): bool
    {
        return $user->role === self::EXECUTIVE_BOARD || $user->role === self::ADMINISTRATOR;
    }

    public static function isPastor(\App\Models\User $user): bool
    {
        return in_array($user->role, self::pastorRoles(), true);
    }

    public static function isRegionalPresbyter(\App\Models\User $user): bool
    {
        return $user->role === self::REGIONAL_PRESBYTER;
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->getLabel()])
            ->toArray();
    }
}
