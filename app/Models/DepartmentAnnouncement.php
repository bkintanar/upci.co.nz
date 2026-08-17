<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentAnnouncement extends Model
{
    protected $fillable = [
        'department_id',
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

    /**
     * Visible to the public.
     *
     * `published_at` previously did nothing. The admin offers a DateTimePicker
     * for it, the column is cast to datetime, the API orders by it and the
     * frontend prints it — but nothing gated on it, so an announcement dated
     * next year went live the moment it was saved. Scheduling silently did not
     * work.
     *
     * A NULL date means "no schedule set", which stays published — that is
     * what `is_published` alone already meant, and treating null as pending
     * would retroactively hide anything saved without a date.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    /**
     * Whether this is waiting for its publish date to arrive. Used by the
     * admin so a scheduled item is distinguishable from a live one.
     */
    public function isScheduled(): bool
    {
        return $this->is_published
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
