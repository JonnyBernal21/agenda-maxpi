<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\ReservaCalendarLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_event_title_includes_the_class_number(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create([
            'name' => 'Roberto Carlos',
            'last_name' => 'Valdez Gonzalez',
        ]);
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $first = $this->book($student, $instructor, $vehicle, '2026-09-02', '07:00');
        $second = $this->book($student, $instructor, $vehicle, '2026-09-04', '07:00');

        $response = $this->getJson(route('admin.calendar.events', [
            'start' => '2026-09-01',
            'end' => '2026-09-08',
        ]));

        $response->assertOk();

        $events = collect($response->json())
            ->where('extendedProps.isAvailable', false)
            ->keyBy('id');

        $this->assertSame(
            ReservaCalendarLabels::bookedEventTitle($student->fresh()->fullName(), 1),
            $events[$first->id]['title']
        );
        $this->assertSame(
            ReservaCalendarLabels::bookedEventTitle($student->fresh()->fullName(), 2),
            $events[$second->id]['title']
        );
        $this->assertSame(1, $events[$first->id]['extendedProps']['classNumber']);
        $this->assertSame(2, $events[$second->id]['extendedProps']['classNumber']);
        $this->assertFalse($events[$first->id]['extendedProps']['isHomeClass']);
        $this->assertStringNotContainsString('A domicilio', $events[$first->id]['title']);
    }

    public function test_cancelled_classes_do_not_consume_class_numbers(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $cancelled = $this->book($student, $instructor, $vehicle, '2026-09-02', '07:00', 'cancelada');
        $active = $this->book($student, $instructor, $vehicle, '2026-09-04', '07:00');

        $response = $this->getJson(route('admin.calendar.events', [
            'start' => '2026-09-01',
            'end' => '2026-09-08',
        ]));

        $events = collect($response->json())->keyBy('id');

        $this->assertSame(1, $events[$active->id]['extendedProps']['classNumber']);
        $this->assertStringStartsWith('Clase- 1 ', $events[$active->id]['title']);
        $this->assertStringNotContainsString('A domicilio', $events[$active->id]['title']);
        $this->assertNull($events[$cancelled->id]['extendedProps']['classNumber']);
        $this->assertStringStartsWith('Cancelada — ', $events[$cancelled->id]['title']);
    }

    public function test_home_class_events_mark_the_modality(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create([
            'name' => 'Perla Monserrath',
            'last_name' => 'Gutierrez Flores',
            'is_home_class' => true,
        ]);
        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'type' => 'manual',
            'status' => 'disponible',
        ]);

        $reserva = $this->book($student, $instructor, $vehicle, '2026-09-02', '15:00');

        $response = $this->getJson(route('admin.calendar.events', [
            'start' => '2026-09-01',
            'end' => '2026-09-08',
        ]));

        $event = collect($response->json())->firstWhere('id', $reserva->id);

        $this->assertTrue($event['extendedProps']['isHomeClass']);
        $this->assertSame(
            ReservaCalendarLabels::bookedEventTitle($student->fresh()->fullName(), 1, false, true),
            $event['title']
        );
        $this->assertStringContainsString('A domicilio', $event['title']);
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
