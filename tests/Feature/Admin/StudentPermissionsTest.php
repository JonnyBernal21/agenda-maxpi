<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_user_can_see_students_but_not_edit_or_delete(): void
    {
        $user = $this->userWithPermissions(['students.view']);
        $student = $this->makeStudent();

        $this->actingAs($user)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee($student->email)
            ->assertDontSee('Agregar alumno')
            ->assertDontSee('js-edit-student', false)
            ->assertDontSee('aria-label="Eliminar a '.$student->fullName().'"', false);

        $this->actingAs($user)
            ->put(route('admin.students.update', $student), $this->payload($student))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.students.destroy', $student))
            ->assertForbidden();
    }

    public function test_edit_permission_allows_update_but_not_delete(): void
    {
        $user = $this->userWithPermissions(['students.view', 'students.edit']);
        $student = $this->makeStudent();

        $this->actingAs($user)
            ->from(route('admin.students.index'))
            ->put(route('admin.students.update', $student), $this->payload($student, [
                'name' => 'Laura',
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'LAURA',
        ]);

        $this->actingAs($user)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('js-edit-student', false)
            ->assertDontSee('aria-label="Eliminar a '.$student->fresh()->fullName().'"', false);

        $this->actingAs($user)
            ->delete(route('admin.students.destroy', $student))
            ->assertForbidden();
    }

    public function test_delete_permission_allows_soft_delete(): void
    {
        $user = $this->userWithPermissions(['students.view', 'students.delete']);
        $student = $this->makeStudent();

        $this->actingAs($user)
            ->from(route('admin.students.index'))
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_manage_permission_is_required_to_create_a_student(): void
    {
        $user = $this->userWithPermissions(['students.view', 'students.edit']);
        $course = Course::query()->create([
            'name' => 'Curso básico',
            'description' => 'Prueba',
            'cost' => 3500,
            'temario' => 'Prueba',
            'num_classes' => 5,
        ]);

        $this->actingAs($user)
            ->post(route('admin.students.store'), [
                'course_id' => $course->id,
                'name' => 'Ana',
                'last_name' => 'García',
                'email' => 'nueva@example.com',
                'phone' => '5512345678',
                'address' => 'Calle 1',
                'city' => 'CDMX',
                'state' => 'CDMX',
                'zip' => '01000',
                'country' => 'México',
                'is_home_class' => 0,
                'payment_method' => Student::PAYMENT_CASH,
                'payment_plan' => 1,
            ])
            ->assertForbidden();
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

    private function makeStudent(): Student
    {
        $course = Course::query()->create([
            'name' => 'Curso básico',
            'description' => 'Prueba',
            'cost' => 3500,
            'temario' => 'Prueba',
            'num_classes' => 5,
        ]);

        return Student::factory()->create([
            'course_id' => $course->id,
            'name' => 'Roberto',
            'last_name' => 'Valdez',
            'email' => 'roberto.permisos@example.com',
            'payment_method' => Student::PAYMENT_CASH,
            'payment_plan' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Student $student, array $overrides = []): array
    {
        return array_merge([
            'course_id' => $student->course_id,
            'name' => $student->name,
            'last_name' => $student->last_name,
            'email' => $student->email,
            'phone' => $student->phone,
            'address' => $student->address,
            'city' => $student->city,
            'state' => $student->state,
            'zip' => $student->zip,
            'country' => $student->country,
            'is_home_class' => 0,
            'discount' => '',
            'payment_method' => $student->payment_method ?: Student::PAYMENT_CASH,
            'payment_plan' => $student->payment_plan ?: 1,
        ], $overrides);
    }
}
