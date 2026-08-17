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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
