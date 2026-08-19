<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_a_vehicle(): void
    {
        $this->actingAs(User::factory()->create());
        $vehicle = Vehicle::factory()->create([
            'modelo' => 'Hyundai i10',
            'plate' => 'ABC-1234',
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $this->from(route('admin.vehicles.index'))
            ->put(route('admin.vehicles.update', $vehicle), [
                'modelo' => 'Hyundai Grand i10',
                'año' => '2025',
                'color' => 'Blanco',
                'plate' => 'ABC-1234',
                'type' => 'automatico',
                'status' => 'en_mantenimiento',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
            ])
            ->assertRedirect(route('admin.vehicles.index'));

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'modelo' => 'Hyundai Grand i10',
            'type' => 'automatico',
            'status' => 'en_mantenimiento',
        ]);
    }

    public function test_admin_can_soft_delete_a_vehicle(): void
    {
        $this->actingAs(User::factory()->create());
        $vehicle = Vehicle::factory()->create([
            'modelo' => 'Nissan Versa',
            'plate' => 'XYZ-890',
            'type' => 'automatico',
            'status' => 'disponible',
        ]);

        $this->from(route('admin.vehicles.index'))
            ->delete(route('admin.vehicles.destroy', $vehicle))
            ->assertRedirect(route('admin.vehicles.index'));

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'plate' => 'XYZ-890',
        ]);
        $this->assertNull(Vehicle::query()->find($vehicle->id));
        $this->get(route('admin.vehicles.index'))
            ->assertOk()
            ->assertSee('0 vehículos en total');
    }

    public function test_guest_cannot_update_or_soft_delete_a_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $this->put(route('admin.vehicles.update', $vehicle), [
            'modelo' => 'Otro',
            'año' => '2024',
            'color' => 'Rojo',
            'plate' => $vehicle->plate,
            'type' => 'manual',
            'status' => 'disponible',
            'owner' => 'Autoescuela MaxPi',
            'owner_id' => 'MAXPI-001',
        ])->assertRedirect(route('login'));

        $this->delete(route('admin.vehicles.destroy', $vehicle))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($vehicle);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'modelo' => $vehicle->modelo,
        ]);
    }
}
