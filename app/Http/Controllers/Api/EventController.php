<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class EventController extends Controller
{
    /**
     * Display a listing of published events (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::published()->with('department')->orderBy('start_date');

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
            'start_date' => $event->start_date->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'location' => $event->location,
            'url' => $event->url,
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
        $event = Event::published()->find($id);
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
            ],
        ]);
    }
}
