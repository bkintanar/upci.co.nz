<?php

namespace App\Http\Controllers\Api;

use App\Models\Church;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ChurchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Church::active()->withCoordinates()->with('organizationalRegion');

        // Filter by address region (NZ geographic)
        if ($request->has('region') && $request->region) {
            $query->byRegion($request->region);
        }

        // Filter by organizational region (North/Central/South) — accepts the region name
        // for wire compatibility with the Vue frontend; internally resolves to region_id.
        if ($request->filled('organizational_region')) {
            $query->whereHas('organizationalRegion', fn ($r) => $r->where('slug', $request->organizational_region));
        }

        // Filter by church status
        if ($request->has('church_status') && $request->church_status) {
            $query->where('church_status', $request->church_status);
        }

        // Filter by service day
        if ($request->has('service_day') && $request->service_day) {
            $query->whereRaw('service_times LIKE ?', ['%"'.$request->service_day.'"%']);
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
                'organizational_region' => $request->organizational_region,
                'church_status' => $request->church_status,
                'service_day' => $request->service_day,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Church $church): JsonResponse
    {
        if (! $church->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatChurchForApi($church),
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
            'organizational_region' => 'nullable|string|exists:regions,slug',
            'church_status' => 'nullable|string|in:Established Church,Daughter Works,Preaching Point',
            'potential_home_group' => 'nullable|boolean',
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

        if (isset($validated['organizational_region'])) {
            $validated['region_id'] = Region::where('slug', $validated['organizational_region'])->value('id');
            unset($validated['organizational_region']);
        }

        $church = Church::create($validated);

        return response()->json([
            'success' => true,
            'data' => $this->formatChurchForApi($church),
            'message' => 'Church created successfully',
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
            'organizational_region' => 'nullable|string|exists:regions,slug',
            'church_status' => 'nullable|string|in:Established Church,Daughter Works,Preaching Point',
            'potential_home_group' => 'nullable|boolean',
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

        if (array_key_exists('organizational_region', $validated)) {
            $validated['region_id'] = $validated['organizational_region']
                ? Region::where('slug', $validated['organizational_region'])->value('id')
                : null;
            unset($validated['organizational_region']);
        }

        $church->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->formatChurchForApi($church),
            'message' => 'Church updated successfully',
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
            'message' => 'Church deleted successfully',
        ]);
    }

    /**
     * Get all unique regions from churches (address/NZ geographic).
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
            'data' => $regions,
        ]);
    }

    /**
     * Get all unique organizational regions (North/Central/South).
     */
    public function organizationalRegions(): JsonResponse
    {
        // slug is the wire format the filter accepts; name is for display
        $regions = Region::orderBy('sort_order')->get(['slug', 'name']);

        return response()->json([
            'success' => true,
            'data' => $regions,
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
            'data' => $serviceDays,
        ]);
    }

    /**
     * Search addresses using NZ Post API.
     */
    public function addressSearch(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2|max:255',
        ]);

        $search = $request->input('search');
        $results = \Upci\FilamentAddressFinder\Forms\Components\AddressFinder::searchAddresses($search);

        return response()->json([
            'success' => true,
            'results' => $results,
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
            'organizational_region' => $church->organizationalRegion?->slug,
            'organizational_region_name' => $church->organizationalRegion?->name,
            'church_status' => $church->church_status,
            'potential_home_group' => $church->potential_home_group,
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
    /**
     * Public leadership summary. Deliberately excludes `email` and `id`:
     * this endpoint is unauthenticated, and those emails are the login
     * identifiers for the admin panel. Nothing in the SPA consumes this
     * block. If a public directory is ever wanted it should be opt-in
     * per user, not every user who happens to hold a role.
     */
    private function formatLeadershipForApi(Church $church): array
    {
        $leadership = $church->leadership()->get();

        return [
            'total_count' => $leadership->count(),
            'pastors' => $church->pastors()->get()->map(function ($pastor) {
                return [
                    'name' => $pastor->name,
                    'role' => $pastor->role->value,
                    'role_label' => $pastor->role->getLabel(),
                ];
            }),
            'elders' => $church->users()->where('role', \App\Enums\UserRole::ELDER)->get()->map(function ($elder) {
                return [
                    'name' => $elder->name,
                    'role' => $elder->role->value,
                    'role_label' => $elder->role->getLabel(),
                ];
            }),
            'deacons' => $church->users()->where('role', \App\Enums\UserRole::DEACON)->get()->map(function ($deacon) {
                return [
                    'name' => $deacon->name,
                    'role' => $deacon->role->value,
                    'role_label' => $deacon->role->getLabel(),
                ];
            }),
            'other_leadership' => $church->users()->whereIn('role', [
                \App\Enums\UserRole::USHER,
                \App\Enums\UserRole::ADMINISTRATOR,
            ])->get()->map(function ($member) {
                return [
                    'name' => $member->name,
                    'role' => $member->role->value,
                    'role_label' => $member->role->getLabel(),
                ];
            }),
        ];
    }
}
