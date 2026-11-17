<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_edited_preview_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 10:00:00', 'America/Mexico_City'));
        [$student, $instructor, $vehicle] = $this->makeActors(2);

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.reservas.schedule'), $this->schedulePayload($student, $instructor, $vehicle, [
                'start_date' => '2026-10-12',
                'class_dates' => ['2026-10-13', '2026-10-15'],
                'class_times' => ['07:00', '09:00'],
                'class_vehicle_ids' => [$vehicle->id, $vehicle->id],
            ]))
            ->assertOk()
            ->assertJsonPath('created', 2);

        $this->assertDatabaseHas('reservas', [
            'student_id' => (string) $student->id,
            'date' => '2026-10-13',
            'time' => '07:00',
        ]);
        $this->assertDatabaseHas('reservas', [
            'student_id' => (string) $student->id,
            'date' => '2026-10-15',
            'time' => '09:00',
        ]);
        $this->assertDatabaseMissing('reservas', [
            'student_id' => (string) $student->id,
            'date' => '2026-10-12',
        ]);
    }

    public function test_user_without_past_schedule_permission_cannot_assign_past_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 10:00:00', 'America/Mexico_City'));
        [$student, $instructor, $vehicle] = $this->makeActors(2);
        $user = $this->userWithPermissions(['reservas.manage']);

        $this->actingAs($user)
            ->postJson(route('admin.reservas.schedule'), $this->schedulePayload($student, $instructor, $vehicle, [
                'start_date' => '2026-11-10',
                'class_dates' => ['2026-11-10', '2026-11-12'],
                'class_times' => ['07:00', '07:00'],
                'class_vehicle_ids' => [$vehicle->id, $vehicle->id],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_user_with_past_schedule_permission_can_assign_past_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 10:00:00', 'America/Mexico_City'));
        [$student, $instructor, $vehicle] = $this->makeActors(2);
        $user = $this->userWithPermissions(['reservas.manage', 'reservas.schedule_past']);

        $this->actingAs($user)
            ->postJson(route('admin.reservas.schedule'), $this->schedulePayload($student, $instructor, $vehicle, [
                'start_date' => '2026-11-10',
                'class_dates' => ['2026-11-10', '2026-11-12'],
                'class_times' => ['07:00', '09:00'],
                'class_vehicle_ids' => [$vehicle->id, $vehicle->id],
            ]))
            ->assertOk()
            ->assertJsonPath('created', 2);

        $this->assertDatabaseHas('reservas', [
            'student_id' => (string) $student->id,
            'date' => '2026-11-10',
            'time' => '07:00',
        ]);
        $this->assertDatabaseHas('reservas', [
            'student_id' => (string) $student->id,
            'date' => '2026-11-12',
            'time' => '09:00',
        ]);
    }

    /**
     * @return array{0: Student, 1: Instructor, 2: Vehicle}
     */
    private function makeActors(int $classes = 2): array
    {
        $course = Course::query()->create([
            'name' => 'Curso básico',
            'description' => 'Prueba',
            'cost' => 3500,
            'temario' => 'Prueba',
            'num_classes' => $classes,
        ]);
        $student = Student::factory()->create([
            'course_id' => $course->id,
        ]);
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        return [$student, $instructor, $vehicle];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function schedulePayload(Student $student, Instructor $instructor, Vehicle $vehicle, array $overrides = []): array
    {
        return array_merge([
            'student_id' => $student->id,
            'start_date' => '2026-11-18',
            'weekdays' => [1],
            'time' => '07:00',
            'instructor_id' => $instructor->id,
            'vehicle_id' => $vehicle->id,
        ], $overrides);
    }

    /**
     * @param  list<string>  $keys
     */
    private function userWithPermissions(array $keys): User
    {
        $role = Role::query()->where('slug', 'recepcionista')->first();
        $role->permissions()->sync(
            Permission::query()->whereIn('key', $keys)->pluck('id')
        );

        return User::factory()->create(['role_id' => $role->id]);
    }
}
