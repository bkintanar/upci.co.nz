<?php

namespace App\Models;

use App\Enums\EventScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'start_date',
        'end_date',
        'location',
        'url',
        'is_published',
        'sort_order',
        'department_id',
        'scope',
        'region_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_published' => 'boolean',
            'scope' => EventScope::class,
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * The national calendar, which is what /events shows by default.
     */
    public function scopeNational($query)
    {
        return $query->where('scope', EventScope::NATIONAL->value);
    }

    /**
     * Events belonging to one region. Scoped on scope as well as region_id so
     * a department event that happens to carry a region does not leak into a
     * region's calendar.
     */
    public function scopeForRegion($query, int $regionId)
    {
        return $query->where('scope', EventScope::REGIONAL->value)
            ->where('region_id', $regionId);
    }

    /**
     * What the calendar lists show (owner, 2026-10-17: "the latest first up
     * until December"): events that are not over yet, up to 31 December of
     * this year. An event that began earlier but is still running stays until
     * its last day.
     *
     * "Today" is New Zealand's date: the app runs in UTC, which is still
     * yesterday here until midday or 1pm. whereDate rather than where,
     * because start_date and end_date are stored as 'Y-m-d H:i:s' and a plain
     * string comparison against 'Y-m-d' drops an event on the last day.
     */
    public function scopeUpcomingThisYear($query)
    {
        $today = now(config('app.display_timezone'));
        $from = $today->toDateString();

        return $query
            ->where(fn ($q) => $q->whereDate('end_date', '>=', $from)
                ->orWhere(fn ($q) => $q->whereNull('end_date')->whereDate('start_date', '>=', $from)))
            ->whereDate('start_date', '<=', $today->copy()->endOfYear()->toDateString());
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
