<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_or_manage_courses(): void
    {
        $course = Course::factory()->create();

        $this->get(route('admin.courses.index'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.courses.store'), $this->payload([
            'name' => 'Curso nuevo',
        ]))->assertRedirect(route('login'));

        $this->put(route('admin.courses.update', $course), $this->payload([
            'name' => 'Curso editado',
        ]))->assertRedirect(route('login'));

        $this->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($course);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => $course->name,
        ]);
    }

    public function test_admin_can_view_the_courses_index(): void
    {
        $this->actingAs(User::factory()->create());
        Course::factory()->create([
            'name' => 'Curso básico',
            'num_classes' => 5,
            'cost' => 3500,
        ]);

        $this->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSee('Cursos')
            ->assertSee('Agregar curso')
            ->assertSee('Curso básico')
            ->assertSee('1 cursos en total');
    }

    public function test_admin_can_store_a_course(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.courses.index'))
            ->post(route('admin.courses.store'), $this->payload())
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'name' => 'Curso defensivo',
            'num_classes' => 6,
            'cost' => 4100.00,
        ]);
    }

    public function test_store_requires_course_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.courses.index'))
            ->post(route('admin.courses.store'), [])
            ->assertRedirect(route('admin.courses.index'))
            ->assertSessionHasErrors(['name', 'description', 'cost', 'temario', 'num_classes']);
    }

    public function test_store_rejects_a_duplicate_course_name(): void
    {
        $this->actingAs(User::factory()->create());
        Course::factory()->create(['name' => 'Curso básico']);

        $this->from(route('admin.courses.index'))
            ->post(route('admin.courses.store'), $this->payload([
                'name' => 'Curso básico',
            ]))
            ->assertRedirect(route('admin.courses.index'))
            ->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_update_a_course(): void
    {
        $this->actingAs(User::factory()->create());
        $course = Course::factory()->create([
            'name' => 'Curso básico',
            'num_classes' => 5,
            'cost' => 3500,
        ]);

        $this->from(route('admin.courses.index'))
            ->put(route('admin.courses.update', $course), $this->payload([
                'name' => 'Curso básico plus',
                'num_classes' => 7,
                'cost' => 3900,
            ]))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Curso básico plus',
            'num_classes' => 7,
            'cost' => 3900.00,
        ]);
    }

    public function test_admin_can_soft_delete_a_course_without_students(): void
    {
        $this->actingAs(User::factory()->create());
        $course = Course::factory()->create([
            'name' => 'Curso nocturno',
        ]);

        $this->from(route('admin.courses.index'))
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Curso nocturno',
        ]);
        $this->assertNull(Course::query()->find($course->id));
        $this->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSee('0 cursos en total')
            ->assertDontSee('>Curso nocturno</span>', false);
    }

    public function test_admin_cannot_delete_a_course_with_enrolled_students(): void
    {
        $this->actingAs(User::factory()->create());
        $course = Course::factory()->create([
            'name' => 'Curso intermedio',
        ]);
        Student::factory()->create([
            'course_id' => $course->id,
        ]);

        $this->from(route('admin.courses.index'))
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'))
            ->assertSessionHasErrors(['course']);

        $this->assertNotSoftDeleted($course);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Curso intermedio',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Curso defensivo',
            'description' => 'Conducción defensiva en avenidas y situaciones de tráfico.',
            'cost' => 4100,
            'temario' => 'Anticipación, distancia de seguimiento y frenado de emergencia.',
            'num_classes' => 6,
        ], $overrides);
    }
}
