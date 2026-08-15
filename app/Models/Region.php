<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    public function churches(): HasMany
    {
        return $this->hasMany(Church::class, 'region_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
