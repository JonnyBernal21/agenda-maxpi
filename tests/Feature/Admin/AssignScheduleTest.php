<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_edited_preview_dates(): void
    {
        $this->actingAs(User::factory()->create());
        $course = Course::query()->create([
            'name' => 'Curso básico',
            'description' => 'Prueba',
            'cost' => 3500,
            'temario' => 'Prueba',
            'num_classes' => 2,
        ]);
        $student = Student::factory()->create([
            'course_id' => $course->id,
        ]);
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $this->postJson(route('admin.reservas.schedule'), [
            'student_id' => $student->id,
            'start_date' => '2026-10-12',
            'weekdays' => [1],
            'time' => '07:00',
            'instructor_id' => $instructor->id,
            'vehicle_id' => $vehicle->id,
            'class_dates' => ['2026-10-13', '2026-10-15'],
            'class_times' => ['07:00', '09:00'],
            'class_vehicle_ids' => [$vehicle->id, $vehicle->id],
        ])
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
}
