<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'meta_description',
        'content',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
    ];
}
