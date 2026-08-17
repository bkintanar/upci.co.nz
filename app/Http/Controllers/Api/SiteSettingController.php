<?php

namespace App\Http\Controllers\Api;

use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class SiteSettingController extends Controller
{
    /**
     * Public site-wide settings. Paths are returned as full URLs so the SPA
     * does not have to know where the public disk is mounted; null means
     * "fall back to the bundled asset".
     */
    public function index(): JsonResponse
    {
        $settings = SiteSetting::current();

        return response()->json([
            'success' => true,
            'data' => [
                'header_logo_url' => $this->url($settings->header_logo_path),
                'footer_logo_url' => $this->url($settings->footer_logo_path),
                'contact_email' => $settings->contact_email,
                'footer_blurb' => $settings->footer_blurb,
                'social_links' => $settings->social_links ?? [],
            ],
        ]);
    }

    private function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.$path);
    }
}
