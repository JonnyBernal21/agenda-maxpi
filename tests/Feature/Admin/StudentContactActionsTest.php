<?php

namespace Tests\Feature\Admin;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentContactActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_index_shows_whatsapp_and_email_actions(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create([
            'phone' => '+52 55 1111 2222',
            'email' => 'roberto@example.com',
        ]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('js-student-whatsapp', false)
            ->assertSee('js-student-email', false)
            ->assertSee('525511112222', false)
            ->assertSee(route('admin.students.schedule-email', $student), false);
    }

    public function test_send_schedule_requires_assigned_classes(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create();

        $this->postJson(route('admin.students.schedule-email', $student))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'El alumno no tiene horarios asignados para enviar.',
            ]);
    }
}
