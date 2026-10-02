<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AGSUpdate extends Model
{
    protected $table = 'ags_updates';

    protected $fillable = [
        'title',
        'content',
        'published_at',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    // A future published_at schedules the update: it stays off the public
    // site until then, the same as DepartmentAnnouncement::scopePublished.
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
