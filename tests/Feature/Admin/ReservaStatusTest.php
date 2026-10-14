<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_admin_can_change_instructor_and_vehicle_of_a_class(): void
    {
        $this->actingAs(User::factory()->create());
        $reserva = $this->makeReserva('pendiente', '2026-10-20', '08:00');
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $this->patchJson(route('admin.reservas.assignment', $reserva), [
            'instructor_id' => $instructor->id,
            'vehicle_id' => $vehicle->id,
        ])
            ->assertOk()
            ->assertJsonPath('instructor_id', $instructor->id)
            ->assertJsonPath('vehicle_id', $vehicle->id)
            ->assertJsonPath('vehicle_type', $vehicle->typeLabel());

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'instructor_id' => (string) $instructor->id,
            'vehicle_id' => (string) $vehicle->id,
        ]);
    }

    public function test_receptionist_cannot_change_instructor_or_vehicle(): void
    {
        $role = Role::query()->where('slug', 'recepcionista')->first();
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $reserva = $this->makeReserva('pendiente', '2026-10-20', '08:00');
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $this->patchJson(route('admin.reservas.assignment', $reserva), [
            'instructor_id' => $instructor->id,
            'vehicle_id' => $vehicle->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'instructor_id' => $reserva->instructor_id,
            'vehicle_id' => $reserva->vehicle_id,
        ]);
    }

    public function test_student_schedule_json_allows_admin_to_edit_assignment(): void
    {
        $this->actingAs(User::factory()->create());
        $reserva = $this->makeReserva('pendiente', '2026-10-20', '08:00');

        $response = $this->getJson(route('admin.students.schedule', $reserva->student_id));

        $response
            ->assertOk()
            ->assertJsonPath('can_edit_assignment', true)
            ->assertJsonPath('classes.0.can_edit', true)
            ->assertJsonPath('classes.0.instructor_id', (int) $reserva->instructor_id)
            ->assertJsonPath('classes.0.vehicle_id', (int) $reserva->vehicle_id);

        $this->assertNotEmpty($response->json('instructors'));
        $this->assertNotEmpty($response->json('vehicles'));
    }

    public function test_student_schedule_json_hides_assignment_edit_for_receptionist(): void
    {
        $role = Role::query()->where('slug', 'recepcionista')->first();
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $reserva = $this->makeReserva('pendiente', '2026-10-20', '08:00');

        $this->getJson(route('admin.students.schedule', $reserva->student_id))
            ->assertOk()
            ->assertJsonPath('can_edit_assignment', false)
            ->assertJsonPath('classes.0.can_edit', false)
            ->assertJsonPath('instructors', [])
            ->assertJsonPath('vehicles', []);
    }

    public function test_admin_cannot_change_assignment_of_a_completed_class(): void
    {
        $this->actingAs(User::factory()->create());
        $reserva = $this->makeReserva('completada', '2026-10-20', '08:00');
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $this->patchJson(route('admin.reservas.assignment', $reserva), [
            'instructor_id' => $instructor->id,
            'vehicle_id' => $vehicle->id,
        ])->assertStatus(422);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'instructor_id' => $reserva->instructor_id,
            'vehicle_id' => $reserva->vehicle_id,
        ]);
    }

    public function test_event_detail_modal_exposes_date_and_time_editors(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('eventModalDateValue', false)
            ->assertSee('data-no-presets="true"', false)
            ->assertSee('id="eventModalTime"', false)
            ->assertSee('id="eventModalInstructor"', false)
            ->assertSee('id="eventModalVehicle"', false)
            ->assertSee('id="eventModalVehicleType"', false);
    }

    public function test_admin_can_reschedule_a_pending_class(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'America/Mexico_City'));
        $this->actingAs(User::factory()->create());
        $reserva = $this->makeReserva('pendiente', '2026-10-20', '09:00');

        $this->patchJson(route('admin.reservas.reschedule', $reserva), [
            'date' => '2026-10-23',
            'time' => '11:00',
        ])
            ->assertOk()
            ->assertJsonPath('date', '2026-10-23')
            ->assertJsonPath('time', '11:00')
            ->assertJsonPath('endTime', '13:00');

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'date' => '2026-10-23',
            'time' => '11:00',
        ]);
    }

    public function test_admin_cannot_reschedule_a_completed_class(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'America/Mexico_City'));
        $this->actingAs(User::factory()->create());
        $reserva = $this->makeReserva('completada', '2026-10-20', '09:00');

        $this->patchJson(route('admin.reservas.reschedule', $reserva), [
            'date' => '2026-10-23',
            'time' => '11:00',
        ])->assertStatus(422);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'date' => '2026-10-20',
            'time' => '09:00',
        ]);
    }

    public function test_admin_cannot_reschedule_into_a_busy_slot(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'America/Mexico_City'));
        $this->actingAs(User::factory()->create());
        $first = $this->makeReserva('pendiente', '2026-10-23', '11:00');
        $second = $this->makeReserva('pendiente', '2026-10-20', '09:00');
        $second->update([
            'instructor_id' => $first->instructor_id,
            'vehicle_id' => $first->vehicle_id,
        ]);

        $this->patchJson(route('admin.reservas.reschedule', $second), [
            'date' => '2026-10-23',
            'time' => '11:00',
        ])->assertStatus(422);

        $this->assertDatabaseHas('reservas', [
            'id' => $second->id,
            'date' => '2026-10-20',
            'time' => '09:00',
        ]);
    }

    public function test_admin_cannot_assign_an_instructor_who_is_already_busy(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->makeReserva('pendiente', '2026-10-20', '08:00');
        $second = $this->makeReserva('pendiente', '2026-10-20', '08:00');

        $this->patchJson(route('admin.reservas.assignment', $second), [
            'instructor_id' => $first->instructor_id,
            'vehicle_id' => $second->vehicle_id,
        ])->assertStatus(422);

        $this->assertDatabaseHas('reservas', [
            'id' => $second->id,
            'instructor_id' => $second->instructor_id,
        ]);
    }

    private function makeReserva(string $status, string $date = '2026-08-28', string $time = '09:30'): Reservas
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
            'date' => $date,
            'time' => $time,
            'status' => $status,
        ]);
    }
}
