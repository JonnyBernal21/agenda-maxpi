<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_email_previews(): void
    {
        $this->get(route('admin.emails.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_email_preview_module(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.emails.index'))
            ->assertOk()
            ->assertSee('Correos')
            ->assertSee('Registro y horarios de clase')
            ->assertSee('Datos de ejemplo');
    }

    public function test_email_html_preview_renders_sample_content(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.emails.html', ['template' => 'schedule-assigned']))
            ->assertOk()
            ->assertSee('Registro y horarios de clase')
            ->assertSee('Ana García López')
            ->assertSee('ana.garcia@example.com')
            ->assertSee('Automático')
            ->assertSee('Manual')
            ->assertDontSee('Instructor')
            ->assertDontSee('Carlos Méndez')
            ->assertSee('Términos y condiciones')
            ->assertSee('$350.00 MXN');
    }

    public function test_email_html_preview_uses_selected_student(): void
    {
        $this->actingAs(User::factory()->create());

        $course = Course::query()->create([
            'name' => 'Curso básico',
            'description' => 'Introducción a la conducción.',
            'cost' => 3500,
            'temario' => 'Controles y maniobras básicas.',
            'num_classes' => 5,
        ]);

        $student = Student::factory()->create([
            'course_id' => $course->id,
            'name' => 'Lucía',
            'last_name' => 'Pérez',
            'email' => 'lucia.perez@example.com',
        ]);

        $this->get(route('admin.emails.html', [
            'template' => 'schedule-assigned',
            'student_id' => $student->id,
        ]))
            ->assertOk()
            ->assertSee('LUCÍA PÉREZ')
            ->assertSee('lucia.perez@example.com')
            ->assertSee('Curso básico');
    }

    public function test_unknown_email_template_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.emails.html', ['template' => 'missing-template']))
            ->assertNotFound();
    }
}
