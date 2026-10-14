<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Vehicle;
use App\Services\UpcomingPaymentsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingPaymentsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_todays_paid_and_upcoming_pending_students(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 15:00:00', 'America/Mexico_City'));

        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $paidStudent = Student::factory()->create([
            'name' => 'Ana',
            'last_name' => 'Lopez',
            'payment_total' => 1200,
            'payment_plan' => 1,
        ]);
        $pendingStudent = Student::factory()->create([
            'name' => 'Diego',
            'last_name' => 'Torres',
            'payment_total' => 2400,
            'payment_plan' => 2,
            'payment_initial' => 1200,
        ]);

        StudentPayment::factory()->create([
            'student_id' => $paidStudent->id,
            'amount' => 1200,
            'paid_at' => '2026-10-13',
        ]);

        Reservas::query()->create([
            'student_id' => (string) $pendingStudent->id,
            'instructor_id' => (string) $instructor->id,
            'vehicle_id' => (string) $vehicle->id,
            'date' => '2026-10-15',
            'time' => '09:00',
            'status' => 'pendiente',
        ]);

        $items = (new UpcomingPaymentsService)->forDashboard(
            Carbon::parse('2026-10-13', 'America/Mexico_City')
        );

        $this->assertCount(2, $items);
        $this->assertSame('ANA LOPEZ', $items[0]['name']);
        $this->assertSame('Pagado', $items[0]['status_label']);
        $this->assertSame('Hoy', $items[0]['date_label']);
        $this->assertSame('DIEGO TORRES', $items[1]['name']);
        $this->assertSame('Pendiente', $items[1]['status_label']);
        $this->assertEquals(1200.0, $items[1]['amount']);
    }
}
