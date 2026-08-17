<?php

namespace App\Http\Controllers\Api;

use App\Models\Church;
use App\Models\Region;
use App\Enums\EventScope;
use App\Models\GalleryItem;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/**
 * Region landing pages (requirement 11): one reusable endpoint for all three
 * regions rather than three hard-coded pages.
 *
 * The response shape is written out explicitly here rather than returning
 * models, so adding a column never silently publishes it — the churches table
 * carries `email` and `pastor_name`, and a leaked email is exactly the defect
 * already fixed once in ChurchController::formatLeadershipForApi().
 *
 * Everything is eager-loaded. formatLeadershipForApi() runs roughly five
 * queries per church; a region page renders every church in the region, so
 * copying that pattern here would multiply it.
 */
class RegionController extends Controller
{
    /**
     * Published regions, with counts rather than full collections — the index
     * feeds a menu and a set of cards, not three complete landing pages.
     */
    public function index(): JsonResponse
    {
        $regions = Region::query()
            ->published()
            ->withCount([
                'churches as churches_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $regions->map(fn (Region $region) => [
                'slug' => $region->slug,
                'name' => $region->name,
                'logo_url' => $this->assetUrl($region->logo_path),
                'presbyter_name' => $region->presbyter_name,
                'churches_count' => $region->churches_count,
            ]),
        ]);
    }

    /**
     * One region's landing page: message, churches, events and gallery.
     */
    public function show(string $slug): JsonResponse
    {
        $region = Region::query()->published()->where('slug', $slug)->first();

        // 404 rather than 403 for an unpublished region: whether a draft
        // region exists is not something the public endpoint should confirm.
        if (! $region) {
            return response()->json(['success' => false, 'message' => 'Region not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $region->slug,
                'name' => $region->name,
                'logo_url' => $this->assetUrl($region->logo_path),
                'intro' => $region->intro,
                'presbyter_name' => $region->presbyter_name,
                'churches' => $this->churches($region),
                'events' => $this->events($region),
                'gallery' => $this->gallery($region),
            ],
        ]);
    }

    /**
     * Deliberately not filtered on coordinates. A church without lat/lng still
     * belongs on its region's page; it simply cannot be plotted, which
     * has_coordinates tells the frontend. See d1e0b0c.
     *
     * @return array<int, array<string, mixed>>
     */
    private function churches(Region $region): array
    {
        return Church::query()
            ->where('region_id', $region->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Church $church) => [
                'id' => $church->id,
                'name' => $church->name,
                'city' => $church->city,
                'pastor_name' => $church->pastor_name,
                'service_times' => $church->service_times,
                'lat' => $church->latitude !== null ? (float) $church->latitude : null,
                'lng' => $church->longitude !== null ? (float) $church->longitude : null,
                'has_coordinates' => $church->hasCoordinates(),
            ])
            ->all();
    }

    /**
     * Only this region's own events. Scoped on `scope` as well as region_id so
     * a department event carrying a region does not surface here.
     *
     * @return array<int, array<string, mixed>>
     */
    private function events(Region $region): array
    {
        return $region->events()
            ->published()
            ->where('scope', EventScope::REGIONAL->value)
            ->orderBy('start_date')
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'start_date' => $event->start_date->format('Y-m-d'),
                'end_date' => $event->end_date?->format('Y-m-d'),
                'location' => $event->location,
                'url' => $event->url,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gallery(Region $region): array
    {
        return $region->galleryItems()
            ->published()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (GalleryItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'image_url' => $this->assetUrl($item->image_path),
            ])
            ->all();
    }

    /**
     * Absolute URLs pass through; stored paths resolve against the public disk.
     */
    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.$path);
    }
}
