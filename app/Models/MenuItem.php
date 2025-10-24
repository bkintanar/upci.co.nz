<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'label',
        'description',
        'url',
        'location',
        'sort_order',
        'is_active',
        'open_in_new_tab',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
    ];

    /**
     * Get the parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get the child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Scope to get only active menu items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get header menu items.
     */
    public function scopeHeader($query)
    {
        return $query->where('location', 'header');
    }

    /**
     * Scope to get footer menu items.
     */
    public function scopeFooter($query)
    {
        return $query->where('location', 'footer');
    }

    /**
     * Scope to get top-level menu items (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }
}
