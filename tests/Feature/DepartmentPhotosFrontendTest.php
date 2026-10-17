<?php

/*
 * The department photos' promises, pinned in the source (direction D3, "Quiet
 * frame", chosen 2026-10-15). There is no JS test runner, so these read the
 * Vue files the way HomepageUpcaStyleTest does.
 */

function departmentPhotosSource(string $path): string
{
    return file_get_contents(resource_path("js/{$path}"));
}

/**
 * The announcement list's markup. It was Department.vue's announcements
 * section until 2026-10-17, when it moved to AnnouncementList.vue so the
 * announcements block on CMS pages shares it; Department.vue now renders that
 * component (checked below).
 */
function announcementsSection(): string
{
    $source = departmentPhotosSource('components/AnnouncementList.vue');

    return substr($source, 0, strpos($source, '</template>'));
}

test('an announcement photo is shown whole, described, and opens the viewer', function () {
    $section = announcementsSection();

    expect($section)->toContain(':alt="a.image_alt || \'\'"')
        ->toContain(':width="a.image_width || null"')
        ->toContain('loading="lazy"')
        ->toContain('@click="openAnnouncementPhoto(a)"')
        ->toContain('<button')
        ->not->toContain('object-cover');
});

test('the announcement layout follows the photo\'s shape', function () {
    $source = departmentPhotosSource('components/AnnouncementList.vue');

    expect($source)->toContain('a.image_height > a.image_width ? \'portrait\' : \'landscape\'')
        ->toContain("portrait: 'mt-5 max-w-[26rem] lg:max-w-none lg:col-start-3 lg:col-span-4")
        ->toContain("landscape: 'mt-5 lg:col-start-3 lg:col-span-10");
});

test('gallery thumbnails are numbered buttons that open the viewer', function () {
    $grid = departmentPhotosSource('components/GalleryGrid.vue');

    expect($grid)->toContain('<button')
        ->toContain('type="button"')
        ->toContain('@click="openViewer(i)"')
        ->toContain('`Open photo ${i + 1} of ${items.value.length}`')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"')
        ->toContain('<PhotoViewer')
        ->toContain('aspect-square');
});

test('the viewer is the shared modal, with a live counter and keys, and no slideshow', function () {
    $viewer = departmentPhotosSource('components/PhotoViewer.vue');

    expect($viewer)->toContain('<Modal')
        ->toContain(':show-close="false"')
        ->toContain('dialog-class="modal-dialog--photo"')
        ->toContain('role="status"')
        ->toContain('aria-live="polite"')
        ->toContain("'ArrowRight'")
        ->toContain("'ArrowLeft'")
        ->toContain('aria-label="Previous photo"')
        ->toContain('aria-label="Next photo"')
        ->not->toContain('setInterval')
        ->not->toContain('setTimeout')
        ->not->toContain('loading="lazy"');

    // No second dialog implementation: Modal owns the <dialog> (comments
    // aside, which may name it).
    expect(preg_replace('/<!--.*?-->/s', '', $viewer))->not->toContain('<dialog');
});

test('the viewer styles live in Modal, after the panel rule, and respect reduced motion', function () {
    $modal = departmentPhotosSource('components/Modal.vue');

    expect($modal)->toContain('.modal-panel.modal-panel--photo')
        ->toContain('.modal-dialog--photo::backdrop')
        ->toContain('prefers-reduced-motion: no-preference')
        ->toContain('showClose: { type: Boolean, default: true }')
        ->and(strpos($modal, '.modal-panel.modal-panel--photo'))->toBeGreaterThan(strpos($modal, '.modal-panel {'));
});

test('a department gallery shows even when empty, saying so', function () {
    expect(departmentPhotosSource('views/Department.vue'))->toContain('empty-message="No photos yet."')
        ->and(departmentPhotosSource('components/GalleryGrid.vue'))->toContain('empty-class="pt-6 text-base text-brand-muted"')
        ->and(departmentPhotosSource('components/BlockState.vue'))->toContain("emptyClass: { type: String, default: 'py-10 text-center text-slate-500' }");
});

test('the photo components build no class names from fragments', function (string $path) {
    preg_match_all('/:?class="([^"]*)"/', departmentPhotosSource($path), $matches);

    foreach ($matches[1] as $classes) {
        expect($classes)->not->toContain('${');
    }
})->with([
    'views/Department.vue',
    'components/GalleryGrid.vue',
    'components/PhotoViewer.vue',
    'components/Modal.vue',
]);

test('department pages and the announcements block share one announcement list', function () {
    expect(departmentPhotosSource('views/Department.vue'))
        ->toContain('<AnnouncementList v-if="department.announcements.length" :items="department.announcements" />')
        ->and(departmentPhotosSource('components/blocks/AnnouncementsBlock.vue'))
        ->toContain('<AnnouncementList v-if="items.length" :items="items" />');
});
