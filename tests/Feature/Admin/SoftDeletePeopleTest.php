<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeletePeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_a_student(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create([
            'name' => 'Ana',
            'last_name' => 'García',
            'email' => 'ana.garcia@example.com',
        ]);

        $this->from(route('admin.students.index'))
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));

        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'email' => 'ana.garcia@example.com',
        ]);
        $this->assertNull(Student::query()->find($student->id));
        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertDontSee('ana.garcia@example.com');
    }

    public function test_admin_can_soft_delete_an_instructor(): void
    {
        $this->actingAs(User::factory()->create());
        $instructor = Instructor::factory()->create([
            'name' => 'Carlos',
            'last_name' => 'Méndez',
            'email' => 'carlos.mendez@example.com',
        ]);

        $this->from(route('admin.instructors.index'))
            ->delete(route('admin.instructors.destroy', $instructor))
            ->assertRedirect(route('admin.instructors.index'));

        $this->assertSoftDeleted('instructors', ['id' => $instructor->id]);
        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'email' => 'carlos.mendez@example.com',
        ]);
        $this->assertNull(Instructor::query()->find($instructor->id));
        $this->get(route('admin.instructors.index'))
            ->assertOk()
            ->assertDontSee('carlos.mendez@example.com');
    }

    public function test_guest_cannot_soft_delete_people(): void
    {
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();

        $this->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('login'));
        $this->delete(route('admin.instructors.destroy', $instructor))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($student);
        $this->assertNotSoftDeleted($instructor);
    }
}
