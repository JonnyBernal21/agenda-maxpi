<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_todays_agenda(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:00:00', 'America/Mexico_City'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Agenda de hoy')
            ->assertSee('Clases y citas programadas')
            ->assertSee('id="dayAgenda"', false);
    }

    public function test_agenda_endpoint_lists_classes_for_the_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 10:30:00', 'America/Mexico_City'));

        $course = Course::factory()->create(['name' => 'Principiante']);
        $student = Student::factory()->create([
            'name' => 'Carlos',
            'last_name' => 'Perez',
            'course_id' => $course->id,
        ]);
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->book($student, $instructor, $vehicle, '2026-10-13', '09:00', 'completada');
        $inProgress = $this->book($student, $instructor, $vehicle, '2026-10-13', '10:00', 'confirmada');
        $this->book($student, $instructor, $vehicle, '2026-10-13', '13:00', 'pendiente');
        $this->book($student, $instructor, $vehicle, '2026-10-13', '15:00', 'cancelada');
        $this->book($student, $instructor, $vehicle, '2026-10-14', '09:00', 'pendiente');

        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.agenda', ['date' => '2026-10-13']))
            ->assertOk()
            ->assertJsonPath('date', '2026-10-13')
            ->assertJsonPath('title', 'Agenda de hoy')
            ->assertJsonCount(3, 'classes')
            ->assertJsonPath('classes.0.status_label', 'Completada')
            ->assertJsonPath('classes.1.id', $inProgress->id)
            ->assertJsonPath('classes.1.live_status', 'en_curso')
            ->assertJsonPath('classes.1.status_label', 'En curso')
            ->assertJsonPath('classes.2.status_label', 'Pendiente')
            ->assertJsonPath('month_dates.0', '2026-10-13')
            ->assertJsonPath('calendar.month', '2026-10')
            ->assertJsonPath('calendar.month_label', 'Octubre 2026');
    }

    public function test_guests_cannot_view_the_agenda(): void
    {
        $this->getJson(route('admin.agenda'))->assertUnauthorized();
    }

    public function test_instructor_only_sees_assigned_classes(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'America/Mexico_City'));

        $mine = Instructor::factory()->create();
        $other = Instructor::factory()->create();
        $student = Student::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->book($student, $mine, $vehicle, '2026-10-13', '09:00');
        $this->book($student, $other, $vehicle, '2026-10-13', '11:00');

        $this->actingAs($mine, 'instructor')
            ->getJson(route('instructor.agenda', ['date' => '2026-10-13']))
            ->assertOk()
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.time', '09:00')
            ->assertJsonPath('subtitle', 'Tus clases y citas del día');
    }

    public function test_instructor_agenda_includes_home_meeting_point(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'America/Mexico_City'));

        $instructor = Instructor::factory()->create();
        $student = Student::factory()->create([
            'is_home_class' => true,
            'meeting_point' => 'Portón negro, tocar el timbre 3',
            'meeting_lat' => 19.282608,
            'meeting_lng' => -99.655701,
            'notes' => 'Llegar 10 minutos antes',
        ]);
        $vehicle = Vehicle::factory()->create();
        $this->book($student, $instructor, $vehicle, '2026-10-13', '09:00');

        $this->actingAs($instructor, 'instructor')
            ->getJson(route('instructor.agenda', ['date' => '2026-10-13']))
            ->assertOk()
            ->assertJsonPath('classes.0.is_home', true)
            ->assertJsonPath('classes.0.meeting_point', 'Portón negro, tocar el timbre 3')
            ->assertJsonPath('classes.0.notes', 'Llegar 10 minutos antes');
    }

    private function book(
        Student $student,
        Instructor $instructor,
        Vehicle $vehicle,
        string $date,
        string $time,
        string $status = 'pendiente',
    ): Reservas {
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
