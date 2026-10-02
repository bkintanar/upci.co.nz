<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class MenuItemController extends Controller
{
    /**
     * Get header menu items.
     */
    public function header(): JsonResponse
    {
        $menuItems = MenuItem::active()
            ->header()
            ->topLevel()
            // Constrained at every depth so inactive rows are never even
            // queried. active() on the top level alone let a disabled child
            // or grandchild reach the public menu.
            ->with(['children' => fn ($q) => $q->active()->with(['children' => fn ($q) => $q->active()])])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                return $this->formatMenuItem($item);
            });

        return response()->json([
            'success' => true,
            'data' => $menuItems,
        ]);
    }

    /**
     * Get footer menu items.
     */
    public function footer(): JsonResponse
    {
        $menuItems = MenuItem::active()
            ->footer()
            ->topLevel()
            // Constrained at every depth so inactive rows are never even
            // queried. active() on the top level alone let a disabled child
            // or grandchild reach the public menu.
            ->with(['children' => fn ($q) => $q->active()->with(['children' => fn ($q) => $q->active()])])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                return $this->formatMenuItem($item);
            });

        return response()->json([
            'success' => true,
            'data' => $menuItems,
        ]);
    }

    /**
     * Format menu item for API response.
     */
    private function formatMenuItem(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'description' => $item->description,
            'url' => $item->url,
            'open_in_new_tab' => $item->open_in_new_tab,
            // Recursive so a third level reaches the frontend. Previously
            // this stopped at children, so a grandchild existed in the admin
            // and in the database but could never appear in the menu — SBQ
            // under Youth Ministry, JBQ under Children's.
            // Filtered here as well as in the eager load, so any depth the
            // load does not constrain still drops inactive items.
            'children' => $item->children
                ->where('is_active', true)
                ->map(fn (MenuItem $child) => $this->formatMenuItem($child))
                ->values(),
        ];
    }
}
