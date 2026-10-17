<?php

namespace App\Http\Controllers\Api;

use App\Models\Page;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\MeasuresImages;

class PageController extends Controller
{
    use MeasuresImages;

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

        if (! $page) {
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
                'content' => $this->forPublic($page->content ?? []),
                'updated_at' => $page->updated_at,
            ],
        ]);
    }

    /**
     * The page's blocks as the public sees them. Every block passes through
     * untouched except announcements (added 2026-10-17 for the Apostolic
     * Bible College page), whose items get what the department pages' own
     * announcements get from DepartmentController:
     * - hidden items stay out of the response, not merely out of sight;
     * - newest first, by their date, and only the newest few when the block
     *   sets a limit (the homepage shows three);
     * - each photo's size, so the page lays a tall flyer beside its words and
     *   a wide photo across the line before it loads;
     * - the photo description only with a photo.
     * The items then have the department announcement shape, so one component
     * (AnnouncementList.vue) renders both.
     *
     * @param  array<int, array{type?: string, data?: array}>  $content
     * @return array<int, array{type?: string, data?: array}>
     */
    private function forPublic(array $content): array
    {
        return array_map(function ($block) {
            if (($block['type'] ?? null) !== 'announcements') {
                return $block;
            }

            $items = collect($block['data']['items'] ?? [])
                ->map(fn ($item) => $item['data'] ?? [])
                ->filter(fn (array $item) => ($item['is_published'] ?? true) !== false && filled($item['title'] ?? null))
                ->sortByDesc(fn (array $item) => $item['published_at'] ?? '')
                ->when((int) ($block['data']['limit'] ?? 0) > 0, fn ($items) => $items->take((int) $block['data']['limit']))
                ->values()
                ->map(fn (array $item, int $i) => [
                    'id' => $i + 1,
                    'title' => $item['title'],
                    'content' => $item['content'] ?? null,
                    'image_path' => $item['image_path'] ?? null,
                    'image_alt' => filled($item['image_path'] ?? null) ? ($item['image_alt'] ?? null) : null,
                    ...$this->imageSize($item['image_path'] ?? null),
                    'published_at' => $item['published_at'] ?? null,
                ])
                ->all();

            $block['data']['items'] = $items;

            return $block;
        }, $content);
    }
}
