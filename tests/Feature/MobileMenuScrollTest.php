<?php

/*
 * "The menu in mobile is not scrollable. It scrolls the page behind it before
 * the menu does" (owner, 2026-10-19). The menu now scrolls on its own within
 * the screen below the bar, and the page behind is locked while it is open.
 * There is no JS test runner, so this reads the component; the behaviour was
 * checked in a phone-sized browser (390x844 and 375x667).
 */
function mobileMenuSource(): string
{
    return file_get_contents(resource_path('js/components/Navbar.vue'));
}

test('the mobile menu scrolls itself, within the screen below the bar', function () {
    expect(mobileMenuSource())
        ->toContain('id="mobile-menu" class="mobile-menu-panel xl:hidden overflow-y-auto overscroll-contain')
        ->toContain('max-height: calc(100dvh - 5rem);');
});

test('the page behind is locked while the menu is open, and unlocked after', function () {
    expect(mobileMenuSource())
        ->toContain('watch(mobileMenuOpen, lockPage)')
        ->toContain("el.style.overflow = locked ? 'hidden' : ''")
        ->toContain('lockPage(false)')
        // Widening past the desktop bar closes the hidden menu, so the page
        // is never left locked.
        ->toContain("window.matchMedia('(min-width: 1280px)')");
});
