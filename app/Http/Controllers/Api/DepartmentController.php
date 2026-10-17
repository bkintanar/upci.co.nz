<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\MeasuresImages;

class DepartmentController extends Controller
{
    // imageSize(): photo sizes for the announcements, shared with the
    // announcements block on CMS pages.
    use MeasuresImages;

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
                'logo_light_path' => $d->logo_light_path,
                'color_theme' => $d->color_theme,
                // The listing page needs a line of real copy per department.
                // Sent whole rather than pre-truncated: the caller decides how
                // much to show, and truncating markdown server-side risks
                // cutting mid-syntax.
                'description' => $d->description,
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
                // A bare disk-relative path, like hero_image and logo_path
                // below. The description only travels with a photo: the admin
                // requires one for a photo, but a description typed with no
                // photo still saves, and it must not reach the page.
                'image_path' => $a->image_path,
                'image_alt' => $a->image_path ? $a->image_alt : null,
                // The page lays a tall flyer beside its words and a wide
                // photo across the line, so it needs the shape before the
                // image loads; the sizes also stop the layout jumping.
                ...$this->imageSize($a->image_path),
                'published_at' => $a->published_at?->toIso8601String(),
            ]);

        // The page's Calendar: what is still to come this year, soonest first
        // (see Event::scopeUpcomingThisYear).
        $events = $department->events()
            ->published()
            ->upcomingThisYear()
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
                'logo_light_path' => $department->logo_light_path,
                'color_theme' => $department->color_theme,
                'scripture_quote' => $department->scripture_quote,
                'announcements' => $announcements,
                'events' => $events,
            ],
        ]);
    }
}
