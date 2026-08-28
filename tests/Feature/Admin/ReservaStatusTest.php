<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_a_pending_class(): void
    {
        $this->actingAs(User::factory()->create());
        $reserva = $this->makeReserva('pendiente');

        $this->patchJson(route('admin.reservas.cancel', $reserva))
            ->assertOk()
            ->assertJson([
                'status' => 'cancelada',
            ]);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'status' => 'cancelada',
        ]);
    }

    public function test_admin_cannot_cancel_a_completed_class(): void
    {
        $this->actingAs(User::factory()->create());
        $reserva = $this->makeReserva('completada');

        $this->patchJson(route('admin.reservas.cancel', $reserva))
            ->assertStatus(422);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'status' => 'completada',
        ]);
    }

    public function test_guest_cannot_cancel_a_class(): void
    {
        $reserva = $this->makeReserva('pendiente');

        $this->patchJson(route('admin.reservas.cancel', $reserva))
            ->assertUnauthorized();

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'status' => 'pendiente',
        ]);
    }

    private function makeReserva(string $status): Reservas
    {
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        return Reservas::query()->create([
            'student_id' => (string) $student->id,
            'instructor_id' => (string) $instructor->id,
            'vehicle_id' => (string) $vehicle->id,
            'date' => '2026-08-28',
            'time' => '09:30',
            'status' => $status,
        ]);
    }
}
