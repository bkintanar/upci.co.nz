<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * Display a listing of all published pages.
     */
    public function index(): JsonResponse
    {
        $pages = Page::where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(function ($page) {
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'meta_description' => $page->meta_description,
                    'sort_order' => $page->sort_order,
                    'updated_at' => $page->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Display the specified page by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_description' => $page->meta_description,
                'content' => $page->content,
                'updated_at' => $page->updated_at,
            ],
        ]);
    }
}
