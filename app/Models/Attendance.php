<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'event',
        'mens',
        'ladies',
        'youth',
        'children',
        'visitors',
        'user_id',
        'church_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mens' => 'integer',
            'ladies' => 'integer',
            'youth' => 'integer',
            'children' => 'integer',
            'visitors' => 'integer',
        ];
    }

    /**
     * Get the user who created this attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the church this attendance record belongs to.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Get the total attendance count.
     */
    public function getTotalAttribute(): int
    {
        return $this->mens + $this->ladies + $this->youth + $this->children + $this->visitors;
    }
}
