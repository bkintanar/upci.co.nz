<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'department',
        'sort_order',
    ];

    public function scopeDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }
}
