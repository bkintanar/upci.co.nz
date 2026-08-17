<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One gallery model serving departments, regions and a general gallery,
 * rather than three parallel implementations (requirement 2).
 *
 * An item with no owner is a general-gallery item. That is a real state, not
 * a missing value — see the owner migration for why the one existing row
 * lives there.
 */
class GalleryItem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'is_published',
        'department',
        'galleryable_type',
        'galleryable_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function galleryable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Items belonging to one owner — a Department or a Region.
     */
    public function scopeOwnedBy(Builder $query, Model $owner): Builder
    {
        return $query->where('galleryable_type', $owner->getMorphClass())
            ->where('galleryable_id', $owner->getKey());
    }

    /**
     * The general gallery: items deliberately owned by nobody.
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->whereNull('galleryable_type');
    }

    /**
     * @deprecated Legacy free-text filter, kept while the one pre-existing row
     * still carries a `department` string that maps to no departments row.
     * Prefer ownedBy().
     */
    public function scopeDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }
}
