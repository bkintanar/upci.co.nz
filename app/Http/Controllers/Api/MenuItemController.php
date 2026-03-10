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
            ->with('children')
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
            ->with('children')
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
            'children' => $item->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'label' => $child->label,
                    'description' => $child->description,
                    'url' => $child->url,
                    'open_in_new_tab' => $child->open_in_new_tab,
                ];
            }),
        ];
    }
}
