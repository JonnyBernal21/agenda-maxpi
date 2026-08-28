<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExtraClassesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_extra_classes_when_editing_a_student(): void
    {
        $this->actingAs(User::factory()->create());
        [$course, $student] = $this->makeStudentWithCourse(8);

        $this->from(route('admin.students.index'))
            ->put(route('admin.students.update', $student), $this->studentPayload($student, $course, [
                ['type' => 'reposicion', 'quantity' => 1, 'notes' => 'Falta justificada'],
                ['type' => 'extra', 'quantity' => 2, 'notes' => ''],
            ]))
            ->assertRedirect(route('admin.students.index'));

        $student->refresh()->load(['course', 'extraClasses']);

        $this->assertCount(2, $student->extraClasses);
        $this->assertSame(3, $student->extraClassesCount());
        $this->assertSame(11, $student->allowedClassesCount());
        $this->assertSame(11, $student->remainingClasses());
        $this->assertDatabaseHas('student_extra_classes', [
            'student_id' => $student->id,
            'type' => 'reposicion',
            'quantity' => 1,
            'notes' => 'Falta justificada',
        ]);
    }

    public function test_empty_extra_class_rows_are_ignored(): void
    {
        $this->actingAs(User::factory()->create());
        [$course, $student] = $this->makeStudentWithCourse(8);

        $this->from(route('admin.students.index'))
            ->put(route('admin.students.update', $student), $this->studentPayload($student, $course, [
                ['type' => '', 'quantity' => 1, 'notes' => ''],
                ['type' => 'cortesia', 'quantity' => 1, 'notes' => 'Cortesía de la escuela'],
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertSame(1, $student->fresh()->extraClasses()->count());
        $this->assertDatabaseHas('student_extra_classes', [
            'student_id' => $student->id,
            'type' => 'cortesia',
            'quantity' => 1,
        ]);
    }

    public function test_updating_extras_replaces_previous_rows(): void
    {
        $this->actingAs(User::factory()->create());
        [$course, $student] = $this->makeStudentWithCourse(8);

        $student->extraClasses()->create([
            'type' => 'reposicion',
            'quantity' => 2,
            'notes' => 'Anterior',
        ]);

        $this->from(route('admin.students.index'))
            ->put(route('admin.students.update', $student), $this->studentPayload($student, $course, [
                ['type' => 'extra', 'quantity' => 1, 'notes' => 'Nueva'],
            ]))
            ->assertRedirect(route('admin.students.index'));

        $student->refresh()->load('extraClasses');

        $this->assertCount(1, $student->extraClasses);
        $this->assertSame('extra', $student->extraClasses->first()->type);
        $this->assertSame(9, $student->allowedClassesCount());
        $this->assertDatabaseMissing('student_extra_classes', [
            'student_id' => $student->id,
            'type' => 'reposicion',
        ]);
    }

    public function test_students_index_includes_extra_classes_in_the_total(): void
    {
        $this->actingAs(User::factory()->create());
        [, $student] = $this->makeStudentWithCourse(8);
        $student->extraClasses()->create([
            'type' => 'extra',
            'quantity' => 2,
        ]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('0 / 10', false)
            ->assertSee('10 restantes', false)
            ->assertSee('data-extra-classes', false);
    }

    public function test_extra_classes_restore_remaining_cupo_after_the_course_is_full(): void
    {
        $this->actingAs(User::factory()->create());
        [$course, $student] = $this->makeStudentWithCourse(1);
        $this->bookClass($student);

        $this->assertFalse($student->fresh()->load('course')->canReserve());

        $this->from(route('admin.students.index'))
            ->put(route('admin.students.update', $student), $this->studentPayload($student, $course, [
                ['type' => 'reposicion', 'quantity' => 1, 'notes' => 'Reponer falta'],
            ]))
            ->assertRedirect(route('admin.students.index'));

        $student = $student->fresh()->load(['course', 'extraClasses']);

        $this->assertTrue($student->canReserve());
        $this->assertSame(1, $student->remainingClasses());
        $this->assertSame(2, $student->allowedClassesCount());
    }

    /**
     * @return array{0: Course, 1: Student}
     */
    private function makeStudentWithCourse(int $numClasses): array
    {
        $course = Course::query()->create([
            'name' => 'Curso intermedio',
            'description' => 'Prueba',
            'cost' => 5200,
            'temario' => 'Prueba',
            'num_classes' => $numClasses,
        ]);

        $student = Student::factory()->create([
            'course_id' => $course->id,
        ]);

        return [$course, $student];
    }

    /**
     * @param  list<array{type: string, quantity: int, notes?: string}>  $extras
     * @return array<string, mixed>
     */
    private function studentPayload(Student $student, Course $course, array $extras): array
    {
        return [
            'course_id' => $course->id,
            'name' => $student->name,
            'last_name' => $student->last_name,
            'email' => $student->email,
            'phone' => $student->phone,
            'address' => $student->address,
            'city' => $student->city,
            'state' => $student->state,
            'zip' => $student->zip,
            'country' => $student->country,
            'extra_classes' => $extras,
        ];
    }

    private function bookClass(Student $student): Reservas
    {
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
            'status' => 'pendiente',
        ]);
    }
}
