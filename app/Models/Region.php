<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'intro',
        'presbyter_name',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function churches(): HasMany
    {
        return $this->hasMany(Church::class, 'region_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
