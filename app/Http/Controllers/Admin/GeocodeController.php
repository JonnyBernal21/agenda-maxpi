<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodeController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        $response = $this->nominatim()->get('https://nominatim.openstreetmap.org/search', [
            'q' => $validated['q'],
            'format' => 'jsonv2',
            'limit' => 5,
            'addressdetails' => 0,
            'countrycodes' => 'mx',
        ]);

        if (! $response->ok()) {
            return response()->json(['results' => []], 502);
        }

        $results = collect($response->json() ?? [])
            ->map(fn (array $row): array => [
                'label' => (string) ($row['display_name'] ?? ''),
                'lat' => isset($row['lat']) ? (float) $row['lat'] : null,
                'lng' => isset($row['lon']) ? (float) $row['lon'] : null,
            ])
            ->filter(fn (array $row): bool => $row['label'] !== '' && $row['lat'] !== null && $row['lng'] !== null)
            ->values();

        return response()->json(['results' => $results]);
    }

    public function reverse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $response = $this->nominatim()->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $validated['lat'],
            'lon' => $validated['lng'],
            'format' => 'jsonv2',
        ]);

        if (! $response->ok()) {
            return response()->json(['label' => ''], 502);
        }

        return response()->json([
            'label' => (string) ($response->json('display_name') ?? ''),
        ]);
    }

    private function nominatim(): \Illuminate\Http\Client\PendingRequest
    {
        $from = (string) config('mail.from.address', 'maxpi.robot@example.com');

        return Http::timeout(8)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'Agenda MaxPi ('.$from.')',
            ]);
    }
}
