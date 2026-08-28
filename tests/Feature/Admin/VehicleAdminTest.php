<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class VehicleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_a_vehicle_with_gallery(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.vehicles.index'))
            ->post(route('admin.vehicles.store'), [
                'modelo' => 'Hyundai i10',
                'año' => '2024',
                'color' => 'Blanco',
                'plate' => 'GAL-0001',
                'type' => 'automatico',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
                'plate_photo' => UploadedFile::fake()->create('placa.jpg', 120, 'image/jpeg'),
                'circulation_card' => UploadedFile::fake()->create('tarjeta.pdf', 80, 'application/pdf'),
                'front_photo' => UploadedFile::fake()->create('frontal.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.vehicles.index'));

        $vehicle = Vehicle::query()->where('plate', 'GAL-0001')->first();

        $this->assertNotNull($vehicle);
        $this->assertNotNull($vehicle->plate_photo_path);
        $this->assertNotNull($vehicle->circulation_card_path);
        $this->assertNotNull($vehicle->front_photo_path);

        Storage::disk('uploads')->assertExists($this->uploadsRelativePath($vehicle->plate_photo_path));
        Storage::disk('uploads')->assertExists($this->uploadsRelativePath($vehicle->circulation_card_path));
        Storage::disk('uploads')->assertExists($this->uploadsRelativePath($vehicle->front_photo_path));
    }

    public function test_store_requires_gallery_files(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.vehicles.index'))
            ->post(route('admin.vehicles.store'), [
                'modelo' => 'Hyundai i10',
                'año' => '2024',
                'color' => 'Blanco',
                'plate' => 'GAL-0002',
                'type' => 'manual',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
            ])
            ->assertRedirect(route('admin.vehicles.index'))
            ->assertSessionHasErrors(['plate_photo', 'circulation_card', 'front_photo']);

        $this->assertDatabaseMissing('vehicles', ['plate' => 'GAL-0002']);
    }

    public function test_store_rejects_an_invalid_image_format(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.vehicles.index'))
            ->post(route('admin.vehicles.store'), [
                'modelo' => 'Hyundai i10',
                'año' => '2024',
                'color' => 'Blanco',
                'plate' => 'GAL-0003',
                'type' => 'manual',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
                'plate_photo' => UploadedFile::fake()->create('placa.gif', 120, 'image/gif'),
                'circulation_card' => UploadedFile::fake()->create('tarjeta.pdf', 80, 'application/pdf'),
                'front_photo' => UploadedFile::fake()->create('frontal.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.vehicles.index'))
            ->assertSessionHasErrors(['plate_photo']);
    }

    public function test_store_rejects_an_oversized_photo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.vehicles.index'))
            ->post(route('admin.vehicles.store'), [
                'modelo' => 'Hyundai i10',
                'año' => '2024',
                'color' => 'Blanco',
                'plate' => 'GAL-0004',
                'type' => 'manual',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
                'plate_photo' => UploadedFile::fake()->create('placa.jpg', 6000, 'image/jpeg'),
                'circulation_card' => UploadedFile::fake()->create('tarjeta.pdf', 80, 'application/pdf'),
                'front_photo' => UploadedFile::fake()->create('frontal.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.vehicles.index'))
            ->assertSessionHasErrors(['plate_photo']);
    }

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

    public function test_updating_a_gallery_photo_replaces_the_previous_file(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->create());

        Storage::disk('uploads')->put('vehicles/old_front.jpg', 'old-front');

        $vehicle = Vehicle::factory()->create([
            'modelo' => 'Nissan Versa',
            'plate' => 'OLD-1111',
            'type' => 'manual',
            'status' => 'disponible',
            'front_photo_path' => 'uploads/vehicles/old_front.jpg',
        ]);

        $this->from(route('admin.vehicles.index'))
            ->put(route('admin.vehicles.update', $vehicle), [
                'modelo' => 'Nissan Versa',
                'año' => '2024',
                'color' => 'Blanco',
                'plate' => 'OLD-1111',
                'type' => 'manual',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
                'front_photo' => UploadedFile::fake()->create('nuevo-frontal.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.vehicles.index'));

        $vehicle->refresh();

        $this->assertNotSame('uploads/vehicles/old_front.jpg', $vehicle->front_photo_path);
        Storage::disk('uploads')->assertMissing('vehicles/old_front.jpg');
        Storage::disk('uploads')->assertExists($this->uploadsRelativePath($vehicle->front_photo_path));
    }

    public function test_update_rejects_a_file_that_is_not_a_real_image(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->create());

        Storage::disk('uploads')->put('vehicles/old_front.jpg', 'old-front');

        $vehicle = Vehicle::factory()->create([
            'modelo' => 'Nissan Versa',
            'plate' => 'FAKE-JPG',
            'type' => 'manual',
            'status' => 'disponible',
            'front_photo_path' => 'uploads/vehicles/old_front.jpg',
        ]);

        $this->from(route('admin.vehicles.index'))
            ->put(route('admin.vehicles.update', $vehicle), [
                'modelo' => 'Nissan Versa',
                'año' => '2024',
                'color' => 'Blanco',
                'plate' => 'FAKE-JPG',
                'type' => 'manual',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
                'front_photo' => UploadedFile::fake()->create('nuevo-frontal.jpg', 120, 'text/plain'),
            ])
            ->assertRedirect(route('admin.vehicles.index'))
            ->assertSessionHasErrors(['front_photo']);

        $this->assertSame('uploads/vehicles/old_front.jpg', $vehicle->fresh()->front_photo_path);
    }

    private function uploadsRelativePath(?string $path): string
    {
        return ltrim(Str::after((string) $path, 'uploads/'), '/');
    }
}
