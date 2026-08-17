<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use App\Enums\EventScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class EventController extends Controller
{
    /**
     * Display a listing of published events (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::published()->with(['department', 'region'])->orderBy('start_date');

        // Requirement 9: the national calendar is a distinct list, not the
        // whole table. Validated against the enum rather than passed through,
        // so an unknown scope is a loud 422 instead of a silently empty list.
        if ($request->filled('scope')) {
            $request->validate([
                'scope' => ['string', Rule::enum(EventScope::class)],
            ]);
            $query->where('scope', $request->string('scope')->toString());
        }

        // Region filters on slug, matching the church locator's wire format.
        // Not restricted to scope=regional here: the caller asks for one or
        // both, and combining them is what a region page actually wants.
        if ($request->filled('region')) {
            $request->validate([
                'region' => ['string', 'exists:regions,slug'],
            ]);
            $query->whereHas('region', fn ($q) => $q->where('slug', $request->string('region')->toString()));
        }

        if ($request->has('from') && $request->from) {
            $query->where(function ($q) use ($request) {
                $q->where('end_date', '>=', $request->from)
                    ->orWhere(function ($q2) use ($request) {
                        $q2->whereNull('end_date')->where('start_date', '>=', $request->from);
                    });
            });
        }
        if ($request->has('to') && $request->to) {
            $query->where('start_date', '<=', $request->to);
        }

        if ($request->filled('department')) {
            $query->whereHas('department', fn ($q) => $q->where('slug', $request->department));
        }

        $events = $query->get()->map(fn (Event $event) => [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'description' => $event->description,
            // Published as a bare disk-relative path, matching every other image
            // field in this API. The client resolves it, so a stored path stays
            // portable across domains and disks.
            'image_path' => $event->image_path,
            'start_date' => $event->start_date->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'location' => $event->location,
            'url' => $event->url,
            'scope' => $event->scope?->value,
            'region' => $event->region ? [
                'slug' => $event->region->slug,
                'name' => $event->region->name,
            ] : null,
            'department' => $event->department ? [
                'slug' => $event->department->slug,
                'name' => $event->department->name,
                'color_theme' => $event->department->color_theme,
            ] : null,
        ]);

        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * Display the specified event (public, published only).
     */
    public function show(string $id): JsonResponse
    {
        $event = Event::published()->with('region')->find($id);
        if (! $event) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'description' => $event->description,
                'start_date' => $event->start_date->format('Y-m-d'),
                'end_date' => $event->end_date?->format('Y-m-d'),
                'location' => $event->location,
                'url' => $event->url,
                'scope' => $event->scope?->value,
                'region' => $event->region ? [
                    'slug' => $event->region->slug,
                    'name' => $event->region->name,
                ] : null,
            ],
        ]);
    }
}
