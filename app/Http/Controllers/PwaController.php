<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    public function manifest(SettingService $settings): JsonResponse
    {
        $setting = $settings->current();
        $icon = $setting->faviconUrl();
        $name = $setting->companyName();
        $tokens = $setting->themeTokens();

        return response()->json([
            'id' => '/',
            'lang' => 'es',
            'name' => $name,
            'short_name' => mb_substr($name, 0, 12),
            'description' => 'Agenda de clases, citas y alumnos.',
            'start_url' => '/login',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'any',
            'background_color' => $tokens['nav'],
            'theme_color' => $tokens['nav'],
            'prefer_related_applications' => false,
            'icons' => [
                [
                    'src' => $icon,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $icon,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
        ]);
    }
}
