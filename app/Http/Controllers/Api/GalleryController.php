<?php

namespace App\Http\Controllers\Api;

use App\Models\GalleryItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GalleryItem::query()->orderBy('sort_order');

        if ($request->filled('department')) {
            $query->department($request->department);
        }

        $items = $query->get()->map(fn (GalleryItem $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => $item->image_path ? (str_starts_with($item->image_path, 'http') ? $item->image_path : asset('storage/' . $item->image_path)) : null,
            'department' => $item->department,
        ]);

        return response()->json(['success' => true, 'data' => $items]);
    }
}
