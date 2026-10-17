<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            // The page the form was on: the contact form is a CMS block, so
            // it can sit on any page (owner request, 2026-10-17). A site path
            // only, never a URL or free text, since it is shown in the admin.
            'page' => ['nullable', 'string', 'max:255', 'regex:/^\/[A-Za-z0-9\-\/]*$/'],
        ], [
            'page.regex' => 'The page must be a path on this site.',
        ]);

        $validated['source_page'] = $validated['page'] ?? null;
        unset($validated['page']);

        $record = ContactMessage::create($validated);

        return response()->json([
            'success' => true,
            'id' => $record->id,
        ], 201);
    }
}
