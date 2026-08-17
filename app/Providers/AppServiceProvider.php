<?php

namespace App\Providers;

use App\Models\Region;
use App\Models\Department;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Gallery ownership is stored as a short alias, not a class path, so
        // moving or renaming a model class does not orphan every row that
        // points at it. enforceMorphMap (rather than morphMap) makes an
        // unregistered owner throw at write time instead of silently writing
        // a full class name that later resolves to nothing.
        Relation::enforceMorphMap([
            'department' => Department::class,
            'region' => Region::class,
        ]);
    }
}
