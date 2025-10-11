<?php

namespace App\Http\Controllers\Api;

use App\Models\Church;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ChurchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Church::active()->withCoordinates();

        // Filter by region
        if ($request->has('region') && $request->region) {
            $query->byRegion($request->region);
        }

        // Filter by service day
        if ($request->has('service_day') && $request->service_day) {
            $query->whereRaw('service_times LIKE ?', ['%"' . $request->service_day . '"%']);
        }

        // Search by name, city, or address
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('city', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%")
                  ->orWhere('region', 'like', "%{$searchTerm}%");
            });
        }

        // Order by featured first, then by name
        $churches = $query->orderBy('is_featured', 'desc')
                         ->orderBy('name')
                         ->get()
                         ->map(function ($church) {
                             return $this->formatChurchForApi($church);
                         });

        return response()->json([
            'success' => true,
            'data' => $churches,
            'count' => $churches->count(),
            'filters' => [
                'region' => $request->region,
                'service_day' => $request->service_day,
                'search' => $request->search,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Church $church): JsonResponse
    {
        if (!$church->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatChurchForApi($church)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'service_times' => 'nullable|array',
            'pastor_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $church = Church::create($validated);

        return response()->json([
            'success' => true,
            'data' => $this->formatChurchForApi($church),
            'message' => 'Church created successfully'
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Church $church): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'service_times' => 'nullable|array',
            'pastor_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $church->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->formatChurchForApi($church),
            'message' => 'Church updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Church $church): JsonResponse
    {
        $church->delete();

        return response()->json([
            'success' => true,
            'message' => 'Church deleted successfully'
        ]);
    }

    /**
     * Get all unique regions from churches.
     */
    public function regions(): JsonResponse
    {
        $regions = Church::active()
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->filter()
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $regions
        ]);
    }

    /**
     * Get all unique service days from churches.
     */
    public function serviceDays(): JsonResponse
    {
        $serviceDays = Church::active()
            ->whereNotNull('service_times')
            ->get()
            ->pluck('service_times')
            ->flatten(1)
            ->pluck('days')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $serviceDays
        ]);
    }

    /**
     * Format church data for API response.
     */
    private function formatChurchForApi(Church $church): array
    {
        return [
            'id' => $church->id,
            'name' => $church->name,
            'address' => $church->address,
            'city' => $church->city,
            'state' => $church->state,
            'region' => $church->region,
            'zip' => $church->zip,
            'country' => $church->country,
            'latitude' => $church->latitude,
            'longitude' => $church->longitude,
            'phone' => $church->phone,
            'email' => $church->email,
            'website' => $church->website,
            'facebook' => $church->facebook,
            'twitter' => $church->twitter,
            'instagram' => $church->instagram,
            'youtube' => $church->youtube,
            'service_times' => $church->formatted_service_times,
            'pastor_name' => $church->pastor_name,
            'description' => $church->description,
            'is_featured' => $church->is_featured,
            'is_active' => $church->is_active,
            'full_address' => $church->full_address,
            'has_coordinates' => $church->hasCoordinates(),
            'leadership' => $this->formatLeadershipForApi($church),
            'created_at' => $church->created_at,
            'updated_at' => $church->updated_at,
        ];
    }

    /**
     * Format leadership data for API response.
     */
    private function formatLeadershipForApi(Church $church): array
    {
        $leadership = $church->leadership()->get();

        return [
            'total_count' => $leadership->count(),
            'pastors' => $church->pastors()->get()->map(function ($pastor) {
                return [
                    'id' => $pastor->id,
                    'name' => $pastor->name,
                    'email' => $pastor->email,
                    'role' => $pastor->role->value,
                    'role_label' => $pastor->role->getLabel(),
                ];
            }),
            'elders' => $church->users()->where('role', \App\Enums\UserRole::ELDER)->get()->map(function ($elder) {
                return [
                    'id' => $elder->id,
                    'name' => $elder->name,
                    'email' => $elder->email,
                    'role' => $elder->role->value,
                    'role_label' => $elder->role->getLabel(),
                ];
            }),
            'deacons' => $church->users()->where('role', \App\Enums\UserRole::DEACON)->get()->map(function ($deacon) {
                return [
                    'id' => $deacon->id,
                    'name' => $deacon->name,
                    'email' => $deacon->email,
                    'role' => $deacon->role->value,
                    'role_label' => $deacon->role->getLabel(),
                ];
            }),
            'other_leadership' => $church->users()->whereIn('role', [
                \App\Enums\UserRole::USHER,
                \App\Enums\UserRole::ADMINISTRATOR
            ])->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->role->value,
                    'role_label' => $member->role->getLabel(),
                ];
            }),
        ];
    }
}
