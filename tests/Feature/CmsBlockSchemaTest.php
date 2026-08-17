<?php

use App\Models\Page;

/**
 * Field names declared inside the `card` Builder block in PageForm.
 *
 * Read from the source rather than by instantiating the schema: Filament's
 * components resolve their children through a Livewire container, so building
 * the form outside a Livewire request needs a fake component and still leans on
 * framework internals. The source is the thing being asserted about anyway —
 * whether the block DECLARES a field — so reading it directly is both simpler
 * and less likely to break on a Filament upgrade.
 *
 * @return array<int, string>
 */
function declaredCardFields(): array
{
    $source = file_get_contents(app_path('Filament/Resources/Pages/Schemas/PageForm.php'));

    $start = strpos($source, "Builder\\Block::make('card')");
    expect($start)->not->toBeFalse('card block not found in PageForm');

    // Bounded by the next block declaration, or the end of file for the last one.
    $next = strpos($source, 'Builder\\Block::make(', $start + 10);
    $region = substr($source, $start, $next === false ? null : $next - $start);

    preg_match_all("/::make\('([a-z_]+)'\)/", $region, $matches);

    return array_values(array_unique($matches[1]));
}

test('the card block declares every field the renderer reads', function () {
    // Filament's Builder rebuilds block state from the DECLARED schema on save.
    // A field the renderer reads but the form never declares is silently
    // dropped the first time an author edits that card. icon_svg was read in
    // CmsPage.vue in 18 places and declared zero times; `variant` and `bio`
    // were added later with the same gap.
    expect(declaredCardFields())
        ->toContain('icon', 'title', 'description', 'link_url', 'link_text')
        ->toContain('icon_svg')
        ->toContain('variant')
        ->toContain('bio');
});

/**
 * Every card field key present in the given pages that the schema does not
 * declare. Extracted so the check can be exercised against known input rather
 * than only against whatever happens to be in the database.
 *
 * @return array<int, string>
 */
function undeclaredCardFields(iterable $pages): array
{
    $declared = declaredCardFields();
    $undeclared = [];

    foreach ($pages as $page) {
        $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

        if (! is_array($blocks)) {
            continue;
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== 'cards') {
                continue;
            }

            foreach ($block['data']['items'] ?? [] as $item) {
                foreach (array_keys($item['data'] ?? []) as $key) {
                    if (! in_array($key, $declared, true)) {
                        $undeclared[] = "{$page->slug}: {$key}";
                    }
                }
            }
        }
    }

    return array_values(array_unique($undeclared));
}

test('the undeclared-field check actually detects one', function () {
    // Guards the guard. The pages that ship a `cards` block come from a seeder
    // that does not run under RefreshDatabase, so asserting over Page::all()
    // alone passes whether or not the check works. This feeds it a card that
    // definitely carries an undeclared key.
    $page = Page::create([
        'slug' => 'schema-probe',
        'title' => 'Schema probe',
        'is_published' => false,
        'content' => [[
            'type' => 'cards',
            'data' => ['items' => [[
                'type' => 'card',
                'data' => ['title' => 'X', 'description' => 'Y', 'not_in_schema' => 'z'],
            ]]],
        ]],
    ]);

    expect(undeclaredCardFields([$page]))->toBe(['schema-probe: not_in_schema']);
});

test('a card using only declared fields is not flagged', function () {
    $page = Page::create([
        'slug' => 'schema-probe-ok',
        'title' => 'Schema probe ok',
        'is_published' => false,
        'content' => [[
            'type' => 'cards',
            'data' => ['items' => [[
                'type' => 'card',
                // Exactly the set the leadership migration writes.
                'data' => [
                    'icon' => 'x.jpg', 'title' => 'Name', 'description' => 'Role',
                    'link_url' => null, 'link_text' => null,
                    'icon_svg' => 'blue-ministry', 'variant' => 'person', 'bio' => null,
                ],
            ]]],
        ]],
    ]);

    expect(undeclaredCardFields([$page]))->toBe([]);
});

test('no card in the CMS carries a field the schema does not declare', function () {
    // The general form of the bug — catches the NEXT undeclared field when it
    // is introduced, rather than when an author loses their work.
    $declared = declaredCardFields();
    $undeclared = [];

    foreach (Page::all() as $page) {
        $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

        if (! is_array($blocks)) {
            continue;
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== 'cards') {
                continue;
            }

            foreach ($block['data']['items'] ?? [] as $item) {
                foreach (array_keys($item['data'] ?? []) as $key) {
                    if (! in_array($key, $declared, true)) {
                        $undeclared[] = "{$page->slug}: {$key}";
                    }
                }
            }
        }
    }

    expect(array_values(array_unique($undeclared)))->toBe([]);
});

test('text and card blocks carry explicit presentation, not inferred', function () {
    // §11.2: five rules used to derive appearance from array position, ordinal,
    // item count, or a substring of the prose. An editor could not control
    // layout, and reordering blocks silently restyled the page. Every existing
    // block was backfilled with the value its rule produced, so a block missing
    // one now means something wrote a block without going through the form.
    $missing = [];

    foreach (Page::all() as $page) {
        $blocks = is_string($page->content) ? json_decode($page->content, true) : $page->content;

        if (! is_array($blocks)) {
            continue;
        }

        foreach ($blocks as $block) {
            $data = $block['data'] ?? [];

            $required = match ($block['type'] ?? null) {
                'text' => ['background', 'style'],
                'cards' => ['background', 'style', 'columns'],
                // two_column hard-coded an even split and a forced grey panel;
                // both are now the author's choice.
                'two_column' => ['ratio', 'right_panel'],
                default => [],
            };

            foreach ($required as $key) {
                if (! array_key_exists($key, $data)) {
                    $missing[] = "{$page->slug} ({$block['type']}): {$key}";
                }
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
