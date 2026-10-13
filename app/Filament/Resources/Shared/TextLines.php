<?php

namespace App\Filament\Resources\Shared;

/**
 * Text typed into a public form (an address, a message), split for display
 * one line per row with TextEntry::listWithLineBreaks().
 *
 * Why not white-space: pre-line: the admin uses Filament's prebuilt
 * stylesheet, which has no whitespace-pre-line class, and set inline the
 * style also keeps the newlines Blade writes around the text, which showed
 * as blank lines above every answer. Rows keep each line escaped like any
 * other state; nothing here becomes HTML.
 */
class TextLines
{
    /**
     * @return list<string>
     */
    public static function split(?string $text): array
    {
        $text = trim((string) $text);

        if ($text === '') {
            return [];
        }

        // \R matches \r\n (what a browser sends from a textarea), \n and \r.
        // A blank line becomes a non-breaking space so the row keeps its
        // height and a paragraph break stays visible; an empty row collapses.
        return array_map(
            fn (string $line) => trim($line) === '' ? "\u{00A0}" : $line,
            preg_split('/\R/u', $text),
        );
    }
}
