<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_meeting_points(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'display_name' => 'Alameda Central, Ciudad de México',
                    'lat' => '19.4356000',
                    'lon' => '-99.1440000',
                ],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.geocode.search', ['q' => 'Alameda Centro']))
            ->assertOk()
            ->assertJsonPath('results.0.label', 'Alameda Central, Ciudad de México')
            ->assertJsonPath('results.0.lat', 19.4356)
            ->assertJsonPath('results.0.lng', -99.144);
    }

    public function test_admin_can_reverse_geocode_a_pin(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Calle Falsa 123, Toluca, México',
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.geocode.reverse', [
                'lat' => 19.2826,
                'lng' => -99.6557,
            ]))
            ->assertOk()
            ->assertJsonPath('label', 'Calle Falsa 123, Toluca, México');
    }
}
