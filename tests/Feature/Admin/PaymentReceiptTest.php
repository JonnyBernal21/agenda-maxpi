<?php

namespace Tests\Feature\Admin;

use App\Mail\StudentPaymentReceiptMail;
use App\Mail\StudentScheduleAssignedMail;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_html_shows_itemized_payment_breakdown(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'name' => 'Roberto',
            'last_name' => 'Valdez',
            'email' => 'roberto@example.com',
            'is_home_class' => true,
            'home_fee_amount' => 100,
            'home_fee_percent' => 0,
            'payment_subtotal' => 4100,
            'discount_amount' => 100,
            'discount_percent' => 0,
            'payment_total' => 4000,
            'payment_plan' => Student::PAYMENT_PLAN_PER_CLASS,
            'payment_method' => Student::PAYMENT_CASH,
        ]);
        $student->payments()->create([
            'amount' => 800,
            'paid_at' => '2026-09-30',
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->get(route('admin.students.receipt', $student))
            ->assertOk()
            ->assertSee('Recibo de pago')
            ->assertSee('ROBERTO VALDEZ')
            ->assertSee('Curso básico')
            ->assertSee('A domicilio')
            ->assertSee('Tarifa a domicilio')
            ->assertSee('$4,000.00')
            ->assertSee('$4,100.00')
            ->assertSee('− $100.00', false)
            ->assertSee('Pago por clase')
            ->assertSee('Abono')
            ->assertSee('$800.00')
            ->assertSee('Saldo pendiente')
            ->assertSee('$3,200.00')
            ->assertSee('MXP-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_receipt_html_shows_home_fee_applied_per_class(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'is_home_class' => true,
            'home_fee_amount' => 100,
            'home_fee_percent' => 0,
            'home_fee_mode' => Student::HOME_FEE_MODE_PER_PAYMENT,
            'payment_subtotal' => 4500,
            'discount_amount' => 0,
            'discount_percent' => 0,
            'payment_total' => 4500,
            'payment_plan' => Student::PAYMENT_PLAN_PER_CLASS,
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->get(route('admin.students.receipt', $student))
            ->assertOk()
            ->assertSee('Tarifa a domicilio')
            ->assertSee('× 5 clases', false)
            ->assertSee('$500.00')
            ->assertSee('$4,500.00');
    }

    public function test_admin_can_send_payment_receipt_email(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'email' => 'lucia.perez@example.com',
            'payment_total' => 3500,
            'payment_subtotal' => 3500,
        ]);

        $this->postJson(route('admin.students.receipt-email', $student))
            ->assertOk()
            ->assertJsonFragment([
                'email' => 'lucia.perez@example.com',
                'message' => 'Recibo enviado por correo a lucia.perez@example.com.',
                'schedule_sent' => false,
                'receipt_sent' => true,
            ]);

        Mail::assertSent(StudentPaymentReceiptMail::class, function (StudentPaymentReceiptMail $mail) use ($student) {
            return $mail->hasTo('lucia.perez@example.com')
                && $mail->student->is($student)
                && ($mail->receipt['total'] ?? null) === 3500.0;
        });
        Mail::assertNotSent(StudentScheduleAssignedMail::class);
    }

    public function test_sending_receipt_also_sends_assigned_schedule(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'email' => 'lucia.perez@example.com',
            'payment_total' => 3500,
            'payment_subtotal' => 3500,
        ]);
        $this->bookClass($student);

        $this->postJson(route('admin.students.receipt-email', $student))
            ->assertOk()
            ->assertJsonFragment([
                'email' => 'lucia.perez@example.com',
                'message' => 'Horarios y recibo enviados por correo a lucia.perez@example.com.',
                'schedule_sent' => true,
                'receipt_sent' => true,
            ]);

        Mail::assertSent(StudentScheduleAssignedMail::class, function (StudentScheduleAssignedMail $mail) {
            return $mail->hasTo('lucia.perez@example.com');
        });
        Mail::assertSent(StudentPaymentReceiptMail::class, function (StudentPaymentReceiptMail $mail) {
            return $mail->hasTo('lucia.perez@example.com');
        });
    }

    public function test_send_receipt_requires_email(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent(['email' => '']);

        $this->postJson(route('admin.students.receipt-email', $student))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'El alumno no tiene un correo para enviar el recibo.',
            ]);

        Mail::assertNothingSent();
    }

    public function test_send_schedule_returns_receipt_preview_urls(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent(['email' => 'ana@example.com']);
        $this->bookClass($student);

        $this->postJson(route('admin.students.schedule-email', $student))
            ->assertOk()
            ->assertJsonFragment([
                'email' => 'ana@example.com',
                'receipt_url' => route('admin.students.receipt', $student),
                'receipt_send_url' => route('admin.students.receipt-email', $student),
            ]);

        Mail::assertSent(StudentScheduleAssignedMail::class);
        Mail::assertNotSent(StudentPaymentReceiptMail::class);
    }

    public function test_payment_history_timeline_distinguishes_full_payment_and_installments(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'name' => 'Lucía',
            'last_name' => 'Pérez',
            'payment_total' => 4000,
            'payment_plan' => 2,
            'payment_initial' => 1000,
        ]);
        $student->payments()->create([
            'amount' => 1000,
            'paid_at' => '2026-09-01',
            'payment_method' => Student::PAYMENT_CASH,
        ]);
        $student->payments()->create([
            'amount' => 3000,
            'paid_at' => '2026-09-15',
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);

        $this->getJson(route('admin.students.payment-history', $student))
            ->assertOk()
            ->assertJsonPath('plan', 'Dos pagos')
            ->assertJsonPath('is_paid', true)
            ->assertJsonPath('payments.0.type', 'Abono')
            ->assertJsonPath('payments.0.amount', 1000)
            ->assertJsonPath('payments.0.balance_after', 3000)
            ->assertJsonPath('payments.1.type', 'Abono')
            ->assertJsonPath('payments.1.balance_after', 0);

        $cashStudent = $this->makeStudent([
            'payment_total' => 3500,
            'payment_plan' => 1,
        ]);
        $cashStudent->payments()->create([
            'amount' => 3500,
            'paid_at' => '2026-09-30',
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->getJson(route('admin.students.payment-history', $cashStudent))
            ->assertOk()
            ->assertJsonPath('payments.0.type', 'Pago completo')
            ->assertJsonPath('is_paid', true);
    }

    public function test_students_index_includes_receipt_actions(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent();

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('id="paymentReceiptModal"', false)
            ->assertSee('id="paymentHistoryModal"', false)
            ->assertSee('js-payment-history', false)
            ->assertSee(route('admin.students.receipt', $student), false)
            ->assertSee(route('admin.students.receipt-email', $student), false)
            ->assertSee(route('admin.students.payment-history', $student), false);
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

    private function bookClass(Student $student): Reservas
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
            'date' => '2026-10-05',
            'time' => '09:00',
            'status' => 'pendiente',
        ]);
    }
}
