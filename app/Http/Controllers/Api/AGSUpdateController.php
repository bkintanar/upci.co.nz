<?php

namespace App\Http\Controllers\Api;

use App\Models\AGSUpdate;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AGSUpdateController extends Controller
{
    public function index(): JsonResponse
    {
        $updates = AGSUpdate::published()
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (AGSUpdate $u) => [
                'id' => $u->id,
                'title' => $u->title,
                'content' => $u->content,
                'published_at' => $u->published_at?->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => $updates]);
    }
}
