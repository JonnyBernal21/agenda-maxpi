<?php

namespace Tests\Feature\Instructor;

use App\Mail\StudentClassEvaluationMail;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\Vehicle;
use App\Support\ClassEvaluationCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClassEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_start_a_class_and_see_the_form(): void
    {
        [$instructor, $reserva] = $this->makeClass('confirmada');

        $this->actingAs($instructor, 'instructor')
            ->post(route('instructor.evaluations.start', $reserva))
            ->assertRedirect(route('instructor.evaluations.edit', $reserva));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'status' => 'en_curso',
        ]);

        $this->actingAs($instructor, 'instructor')
            ->get(route('instructor.evaluations.edit', $reserva))
            ->assertOk()
            ->assertSee('Calificación por habilidad')
            ->assertSee('Tipo de vehículo')
            ->assertSee('Ajuste del asiento, espejos y cinturón')
            ->assertSee('Cancelar')
            ->assertDontSee('Volver a mis clases')
            ->assertDontSee('Mis clases')
            ->assertDontSee('Cerrar sesión');

        $this->actingAs($instructor, 'instructor')
            ->get(route('instructor.dashboard'))
            ->assertRedirect(route('instructor.evaluations.edit', $reserva));
    }

    public function test_canceling_the_evaluation_returns_to_the_dashboard(): void
    {
        [$instructor, $reserva] = $this->makeClass('confirmada');

        $this->actingAs($instructor, 'instructor')
            ->post(route('instructor.evaluations.start', $reserva));

        $this->actingAs($instructor, 'instructor')
            ->post(route('instructor.evaluations.cancel', $reserva))
            ->assertRedirect(route('instructor.dashboard'));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'status' => 'confirmada',
        ]);

        $this->actingAs($instructor, 'instructor')
            ->get(route('instructor.dashboard'))
            ->assertOk();
    }

    public function test_another_instructor_cannot_start_the_class(): void
    {
        [, $reserva] = $this->makeClass('pendiente');
        $other = Instructor::factory()->create();

        $this->actingAs($other, 'instructor')
            ->post(route('instructor.evaluations.start', $reserva))
            ->assertForbidden();
    }

    public function test_guest_cannot_start_a_class(): void
    {
        [, $reserva] = $this->makeClass('pendiente');

        $this->post(route('instructor.evaluations.start', $reserva))
            ->assertRedirect(route('login'));
    }

    public function test_submitting_the_evaluation_shows_the_summary(): void
    {
        [$instructor, $reserva] = $this->makeClass('en_curso');

        $this->actingAs($instructor, 'instructor')
            ->post(route('instructor.evaluations.start', $reserva));

        $this->actingAs($instructor, 'instructor')
            ->put(route('instructor.evaluations.update', $reserva), [
                'vehicle_type' => 'manual',
                'notes' => 'Buen control del volante.',
                'scores' => $this->fullScores(4),
            ])
            ->assertRedirect(route('instructor.evaluations.summary', $reserva));

        $this->actingAs($instructor, 'instructor')
            ->get(route('instructor.evaluations.summary', $reserva))
            ->assertOk()
            ->assertSee('Resumen de resultados')
            ->assertSee('4.0')
            ->assertSee('Buen control del volante.');
    }

    public function test_sending_the_evaluation_emails_the_student_and_completes_the_class(): void
    {
        Mail::fake();
        [$instructor, $reserva] = $this->makeClass('en_curso');

        $this->actingAs($instructor, 'instructor')
            ->post(route('instructor.evaluations.start', $reserva));

        $this->actingAs($instructor, 'instructor')
            ->put(route('instructor.evaluations.update', $reserva), [
                'vehicle_type' => 'automatico',
                'notes' => 'Sigue practicando la reversa.',
                'scores' => $this->fullScores(5),
            ]);

        $this->actingAs($instructor, 'instructor')
            ->post(route('instructor.evaluations.send', $reserva))
            ->assertRedirect(route('instructor.dashboard'));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'status' => 'completada',
        ]);

        Mail::assertSent(StudentClassEvaluationMail::class, function (StudentClassEvaluationMail $mail) use ($reserva) {
            return $mail->student->is($reserva->student)
                && $mail->hasTo($reserva->student->email);
        });
    }

    public function test_instructor_dashboard_lists_only_assigned_classes_with_start_action(): void
    {
        [$instructor, $reserva] = $this->makeClass('pendiente', '2026-10-14');
        $this->travelTo(Carbon::parse('2026-10-14 09:00:00', 'America/Mexico_City'));

        $this->actingAs($instructor, 'instructor')
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Clases de hoy')
            ->assertSee('Clases completadas')
            ->assertSee('Pendientes de hoy')
            ->assertSee('Clases de la semana')
            ->assertSee('Iniciar')
            ->assertSee($reserva->student->name)
            ->assertSee('eventModalHomeWrap', false)
            ->assertSee('Punto de encuentro', false);
    }

    /**
     * @return array{0: Instructor, 1: Reservas}
     */
    private function makeClass(string $status, string $date = '2026-10-14'): array
    {
        $instructor = Instructor::factory()->create();
        $student = Student::factory()->create();
        $vehicle = Vehicle::factory()->create(['type' => 'manual', 'status' => 'disponible']);
        $reserva = Reservas::query()->create([
            'student_id' => (string) $student->id,
            'instructor_id' => (string) $instructor->id,
            'vehicle_id' => (string) $vehicle->id,
            'date' => $date,
            'time' => '08:00',
            'status' => $status,
        ]);

        return [$instructor, $reserva];
    }

    /**
     * @return array<string, int>
     */
    private function fullScores(int $score): array
    {
        return collect(ClassEvaluationCatalog::ids())
            ->mapWithKeys(fn (string $id) => [$id => $score])
            ->all();
    }
}
