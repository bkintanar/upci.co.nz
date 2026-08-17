<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class DepartmentController extends Controller
{
    /**
     * Display a listing of published departments (public).
     */
    public function index(): JsonResponse
    {
        $departments = Department::published()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Department $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'slug' => $d->slug,
                'hero_image' => $d->hero_image,
                'logo_path' => $d->logo_path,
                'color_theme' => $d->color_theme,
            ]);

        return response()->json(['success' => true, 'data' => $departments]);
    }

    /**
     * Display a department with announcements and events (public, published only).
     */
    public function show(string $slug): JsonResponse
    {
        $department = Department::published()->where('slug', $slug)->first();

        if (! $department) {
            return response()->json(['success' => false, 'message' => 'Department not found'], 404);
        }

        // The announcements() relation already applies orderBy('sort_order'),
        // so this only adds the tiebreaker: equal sort_order falls back to
        // newest first.
        $announcements = $department->announcements()
            ->published()
            ->orderByDesc('published_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'published_at' => $a->published_at?->toIso8601String(),
            ]);

        $events = $department->events()
            ->published()
            ->orderBy('start_date')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'slug' => $e->slug,
                'description' => $e->description,
                'start_date' => $e->start_date->format('Y-m-d'),
                'end_date' => $e->end_date?->format('Y-m-d'),
                'location' => $e->location,
                'url' => $e->url,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
                'hero_image' => $department->hero_image,
                'logo_path' => $department->logo_path,
                'color_theme' => $department->color_theme,
                'scripture_quote' => $department->scripture_quote,
                'announcements' => $announcements,
                'events' => $events,
            ],
        ]);
    }
}
