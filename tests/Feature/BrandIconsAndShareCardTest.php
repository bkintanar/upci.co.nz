<?php

use Illuminate\Support\Str;

/*
 * The favicon and the share card (owner, 2026-10-17): the globe from the UPCI
 * NZ logo replaces Laravel's default icon, and link previews get a 1200×630
 * card. The card's tags are in the page as served, because link-preview
 * crawlers do not run JavaScript.
 */
test('the icon files exist at the sizes the page promises', function (string $file, int $width, int $height) {
    $path = public_path($file);

    expect(file_exists($path))->toBeTrue()
        ->and(array_slice(getimagesize($path), 0, 2))->toBe([$width, $height]);
})->with([
    ['favicon-16x16.png', 16, 16],
    ['favicon-32x32.png', 32, 32],
    ['apple-touch-icon.png', 180, 180],
    ['icon-192.png', 192, 192],
    ['icon-512.png', 512, 512],
    ['og-image.jpg', 1200, 630],
]);

test('favicon.svg is no longer Laravel\'s logo', function () {
    $svg = file_get_contents(public_path('favicon.svg'));

    expect($svg)->toContain('data:image/png;base64,')
        // A path from Laravel's own mark.
        ->not->toContain('M162.041 38.7592');
});

test('the page links the icons and serves the share card without JavaScript', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<link rel="icon" href="/favicon.ico?v=2" sizes="48x48">')
        ->toContain('<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=2">')
        ->toContain('<link rel="manifest" href="/site.webmanifest?v=2">')
        ->toContain('<meta property="og:image" content="'.url('/og-image.jpg').'">')
        ->toContain('<meta property="og:image:width" content="1200">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">');

    expect(Str::startsWith(url('/og-image.jpg'), 'http'))->toBeTrue();
});

test('the manifest names the Android icons', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

    expect(array_column($manifest['icons'], 'src'))->toBe(['/icon-192.png', '/icon-512.png']);
});

test('the app updates the served Open Graph tags instead of adding second ones', function () {
    expect(file_get_contents(resource_path('js/composables/usePageMeta.js')))
        ->toContain("const attribute = name.startsWith('og:') ? 'property' : 'name'");
});
