<?php

namespace App\Http\Controllers\Api;

use App\Models\Region;
use App\Models\Department;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class GalleryController extends Controller
{
    /**
     * One endpoint for all three galleries (requirement 2).
     *
     *   ?department=youth   department gallery
     *   ?region=northern    region gallery
     *   ?owner=general      the general gallery — items owned by nobody
     *   (no filter)         everything published
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'department' => ['sometimes', 'string', 'exists:departments,slug'],
            'region' => ['sometimes', 'string', 'exists:regions,slug'],
            'owner' => ['sometimes', 'string', 'in:general'],
        ]);

        // published() is new and load-bearing: the table had no visibility
        // column, so every uploaded item was public the moment it was saved.
        $query = GalleryItem::query()
            ->published()
            ->with('galleryable')
            ->orderBy('sort_order');

        if ($request->filled('department')) {
            $department = Department::where('slug', $request->string('department')->toString())->firstOrFail();
            $query->ownedBy($department);
        }

        if ($request->filled('region')) {
            $region = Region::where('slug', $request->string('region')->toString())->firstOrFail();
            $query->ownedBy($region);
        }

        if ($request->string('owner')->toString() === 'general') {
            $query->general();
        }

        $items = $query->get()->map(fn (GalleryItem $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => $this->imageUrl($item),
            'owner' => $this->owner($item),
            // Legacy free-text label, retained while one row still carries a
            // department string that maps to no departments row. See the
            // gallery owner migration.
            'department' => $item->department,
        ]);

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Absolute URLs are passed through; stored paths resolve against the
     * public disk.
     */
    private function imageUrl(GalleryItem $item): ?string
    {
        if (! $item->image_path) {
            return null;
        }

        return str_starts_with($item->image_path, 'http')
            ? $item->image_path
            : asset('storage/'.$item->image_path);
    }

    /**
     * A null owner is the general gallery, which is a real state rather than
     * missing data — so the key is always present and the type says which.
     *
     * @return array{type: string, slug: string|null, name: string|null}
     */
    private function owner(GalleryItem $item): array
    {
        $owner = $item->galleryable;

        if ($owner === null) {
            return ['type' => 'general', 'slug' => null, 'name' => null];
        }

        return [
            'type' => $owner->getMorphClass(),
            'slug' => $owner->slug,
            'name' => $owner->name,
        ];
    }
}
