<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Site-wide settings. Always exactly one row — use SiteSetting::current().
 */
class SiteSetting extends Model
{
    protected $fillable = [
        'header_logo_path',
        'footer_logo_path',
        'contact_email',
        'footer_blurb',
        'social_links',
    ];

    protected function casts(): array
    {
        return ['social_links' => 'array'];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
