<?php

namespace Upci\FilamentAddressFinder;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Upci\FilamentAddressFinder\Forms\Components\AddressFinder;

class FilamentAddressFinderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-address-finder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews()
            ->hasAssets();
    }

    public function packageBooted(): void
    {
        // Register assets
        $this->registerAssets();
    }

    protected function registerAssets(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-address-finder');

        // Register JavaScript asset
        FilamentAsset::register([
            Js::make('filament-address-finder-scripts', __DIR__ . '/../resources/js/address-finder.js'),
        ], package: 'upci/filament-address-finder');
    }
}
