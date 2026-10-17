<?php

/*
 * A YouTube or Vimeo video pasted into the CMS fills the column at 16:9
 * (2026-10-17, for the ABC Principal's Corner video). Their embed code is a
 * fixed 560x315, which sat small on a desktop and squashed on a phone; the
 * site's CSS already did this for Facebook videos.
 */
test('YouTube and Vimeo players are sized like Facebook videos', function (string $player) {
    $css = file_get_contents(resource_path('css/app.css'));
    $start = strpos($css, '.cms-text-content iframe[src*="/plugins/video"]');
    $rule = substr($css, $start, strpos($css, '}', $start) - $start);

    foreach (['.cms-text-content', '.two-column-content', '.prose', '.embed-container'] as $container) {
        expect($rule)->toContain("{$container} iframe[src*=\"{$player}\"]");
    }

    expect($rule)->toContain('aspect-ratio: 16 / 9')->toContain('width: 100%');
})->with(['/plugins/video', 'youtube.com/embed/', 'youtube-nocookie.com/embed/', 'player.vimeo.com/video/']);
