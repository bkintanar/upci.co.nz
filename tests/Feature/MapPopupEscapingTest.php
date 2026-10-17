<?php

/*
 * Leaflet's bindPopup() and innerHTML take HTML strings. Church name, address,
 * city and region are editable by local admins, so any of them reaching those
 * sinks raw is stored XSS: on the public locator, and in the admin church form's
 * address finder, where it runs in the next (national) admin to open the church.
 *
 * These are source checks because the suite has no JS runner.
 */

$addressFinderFiles = [
    'package source' => 'packages/filament-address-finder/resources/js/address-finder.js',
    'published asset' => 'public/js/upci/filament-address-finder/filament-address-finder-scripts.js',
];

test('every locator popup interpolation is escaped', function () {
    $source = file_get_contents(base_path('resources/js/components/blocks/ChurchLocatorBlock.vue'));

    preg_match_all('/\.bindPopup\(`(.*?)`/s', $source, $popups);

    expect($popups[1])->not->toBeEmpty();

    foreach ($popups[1] as $popup) {
        preg_match_all('/\$\{(.*?)\}/', $popup, $interpolations);

        foreach ($interpolations[1] as $expression) {
            expect($expression)->toStartWith('escapeHtml(');
        }
    }
});

test('the admin address finder escapes its popup labels', function (string $path) {
    $source = file_get_contents(base_path($path));

    expect($source)->toContain('function escapeHtml(')
        ->and($source)->not->toMatch('/\.bindPopup\(address\.label/')
        ->and($source)->toContain('.bindPopup(escapeHtml(address.label))');
})->with($addressFinderFiles);

test('the admin address finder builds its results dropdown without an HTML string', function (string $path) {
    $source = file_get_contents(base_path($path));

    expect($source)->not->toContain('dropdown.innerHTML = results')
        ->and($source)->not->toContain('onclick="selectAddress(')
        ->and($source)->toContain('main.textContent = result.label');
})->with($addressFinderFiles);

test('the published address finder asset matches the package source', function () use ($addressFinderFiles) {
    expect(file_get_contents(base_path($addressFinderFiles['published asset'])))
        ->toBe(file_get_contents(base_path($addressFinderFiles['package source'])));
});
