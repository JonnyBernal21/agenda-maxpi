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

class StudentPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_index_shows_first_class_and_payment_status(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'name' => 'Roberto',
            'last_name' => 'Valdez',
            'payment_total' => 4000,
            'payment_plan' => 2,
            'payment_initial' => 1000,
            'payment_method' => Student::PAYMENT_CASH,
        ]);
        $student->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_CASH,
        ]);
        $this->bookClass($student, '2026-09-28', '09:00');
        $this->bookClass($student, '2026-10-05', '11:00');

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('28/09/2026')
            ->assertSee('9:00 AM', false)
            ->assertSee('Efectivo')
            ->assertSee('Dos pagos')
            ->assertSee('Debe $3,000.00')
            ->assertSee('Abonar')
            ->assertDontSee('>Liquidado</span>', false);
    }

    public function test_paid_students_show_liquidated_without_pay_button(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'name' => 'Marisela',
            'payment_total' => 3500,
            'payment_plan' => 1,
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);
        $student->payments()->create([
            'amount' => 3500,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Transferencia')
            ->assertSee('Un solo pago')
            ->assertSee('Liquidado')
            ->assertDontSee('js-student-pay', false)
            ->assertDontSee('Debe $');
    }

    public function test_admin_can_register_an_installment_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'payment_total' => 4000,
            'payment_plan' => 2,
            'payment_initial' => 1000,
        ]);
        $student->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.payments.store', $student), [
                '_form' => 'student-payment',
                'student_id' => $student->id,
                'amount' => 1500,
                'payment_method' => Student::PAYMENT_TRANSFER,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('student_payments', [
            'student_id' => $student->id,
            'amount' => 1500.00,
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);
        $this->assertSame(1500.0, $student->fresh()->load('payments')->balanceDue());
    }

    public function test_cannot_pay_more_than_the_remaining_balance(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'payment_total' => 2000,
            'payment_plan' => 2,
            'payment_initial' => 500,
        ]);
        $student->payments()->create([
            'amount' => 500,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.payments.store', $student), [
                '_form' => 'student-payment',
                'amount' => 2000,
                'payment_method' => Student::PAYMENT_CASH,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('amount');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStudent(array $overrides = []): Student
    {
        $course = Course::query()->create([
            'name' => 'Curso básico',
            'description' => 'Prueba',
            'cost' => 4000,
            'temario' => 'Prueba',
            'num_classes' => 5,
        ]);

        return Student::factory()->create(array_merge([
            'course_id' => $course->id,
            'payment_method' => Student::PAYMENT_CASH,
            'payment_plan' => 1,
        ], $overrides));
    }

    private function bookClass(Student $student, string $date, string $time): Reservas
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
            'date' => $date,
            'time' => $time,
            'status' => 'pendiente',
        ]);
    }
}
