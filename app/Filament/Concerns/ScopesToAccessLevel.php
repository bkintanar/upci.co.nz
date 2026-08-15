<?php

namespace App\Filament\Concerns;

use App\Enums\AccessLevel;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralises access-level driven list-query scoping for Filament resources.
 *
 * Each consuming resource must define two closures:
 *  - localScope():    (Builder $q, int $churchId) => Builder  — filter to one church
 *  - regionalScope(): (Builder $q, int $regionId) => Builder  — filter to one region
 *
 * For departmental permissions later, add a `departmentalScope()` closure
 * and extend the match below.
 */
trait ScopesToAccessLevel
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || ! $user->access_level) {
            return $query->whereRaw('1=0');
        }

        return match ($user->access_level) {
            AccessLevel::NATIONAL => $query,
            AccessLevel::REGIONAL => (static::regionalScope())($query, $user->region_id ?? -1),
            AccessLevel::LOCAL    => (static::localScope())($query, $user->church_id ?? -1),
        };
    }

    /** @return Closure(Builder, int): Builder */
    abstract protected static function localScope(): Closure;

    /** @return Closure(Builder, int): Builder */
    abstract protected static function regionalScope(): Closure;
}
