<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_a_student_with_payment_details(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(5200);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'discount' => '%10',
                'payment_method' => Student::PAYMENT_TRANSFER,
                'payment_plan' => 2,
                'payment_initial' => 1500,
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 0,
            'payment_subtotal' => 5200.00,
            'discount_percent' => 10.00,
            'discount_amount' => 520.00,
            'payment_total' => 4680.00,
            'payment_method' => Student::PAYMENT_TRANSFER,
            'payment_plan' => 2,
            'payment_initial' => 1500.00,
        ]);

        $student = Student::query()->where('email', 'ana.garcia@example.com')->first();

        $this->assertDatabaseHas('student_payments', [
            'student_id' => $student->id,
            'amount' => 1500.00,
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);
        $this->assertSame(now()->toDateString(), $student->payments()->first()?->paid_at?->toDateString());
    }

    public function test_home_class_adds_the_entered_amount_fee(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'home_fee' => '100',
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
            'home_fee_amount' => 100.00,
            'home_fee_percent' => 2.86,
            'home_fee_mode' => Student::HOME_FEE_MODE_TOTAL,
            'payment_subtotal' => 3600.00,
            'payment_total' => 3600.00,
        ]);
    }

    public function test_home_class_can_store_meeting_notes(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'meeting_point' => 'Portón negro, tocar el timbre 3',
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
            'meeting_point' => 'Portón negro, tocar el timbre 3',
        ]);
    }

    public function test_home_class_can_store_a_map_meeting_point(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'meeting_lat' => 19.282608,
                'meeting_lng' => -99.655701,
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $student = Student::query()->where('email', 'ana.garcia@example.com')->first();

        $this->assertNotNull($student);
        $this->assertEqualsWithDelta(19.282608, (float) $student->meeting_lat, 0.000001);
        $this->assertEqualsWithDelta(-99.655701, (float) $student->meeting_lng, 0.000001);
    }

    public function test_school_class_can_store_general_notes(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 0,
                'general_notes' => 'Prefiere clases por la mañana',
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 0,
            'notes' => 'Prefiere clases por la mañana',
        ]);
    }

    public function test_school_class_ignores_meeting_notes(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 0,
                'meeting_point' => 'No debería guardarse',
                'meeting_lat' => 19.4326,
                'meeting_lng' => -99.1332,
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 0,
            'meeting_point' => null,
            'meeting_lat' => null,
            'meeting_lng' => null,
        ]);
    }

    public function test_home_class_fee_is_included_before_percent_discount(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(5200);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'home_fee' => '100',
                'discount' => '%10',
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
            'home_fee_amount' => 100.00,
            'payment_subtotal' => 5300.00,
            'discount_percent' => 10.00,
            'discount_amount' => 530.00,
            'payment_total' => 4770.00,
        ]);
    }

    public function test_home_class_percent_fee_applies_over_the_course_total(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'home_fee' => '%10',
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
            'home_fee_percent' => 10.00,
            'home_fee_amount' => 350.00,
            'payment_subtotal' => 3850.00,
            'payment_total' => 3850.00,
        ]);
    }

    public function test_home_class_fee_can_apply_to_each_class(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'home_fee' => '100',
                'home_fee_mode' => Student::HOME_FEE_MODE_PER_PAYMENT,
                'payment_plan' => Student::PAYMENT_PLAN_PER_CLASS,
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
            'home_fee_amount' => 100.00,
            'home_fee_mode' => Student::HOME_FEE_MODE_PER_PAYMENT,
            'payment_plan' => 0,
            'payment_subtotal' => 4300.00,
            'payment_total' => 4300.00,
        ]);
    }

    public function test_home_class_fee_can_apply_to_each_installment(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'home_fee' => '100',
                'home_fee_mode' => Student::HOME_FEE_MODE_PER_PAYMENT,
                'payment_plan' => 3,
                'payment_initial' => 1300,
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'home_fee_amount' => 100.00,
            'home_fee_mode' => Student::HOME_FEE_MODE_PER_PAYMENT,
            'payment_plan' => 3,
            'payment_subtotal' => 3800.00,
            'payment_total' => 3800.00,
            'payment_initial' => 1300.00,
        ]);
    }

    public function test_single_payment_with_per_class_home_fee_charges_the_course_now(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'home_fee' => '100',
                'home_fee_mode' => Student::HOME_FEE_MODE_PER_PAYMENT,
                'payment_plan' => Student::PAYMENT_PLAN_SINGLE,
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $student = Student::query()->where('email', 'ana.garcia@example.com')->first();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'home_fee_amount' => 100.00,
            'home_fee_mode' => Student::HOME_FEE_MODE_PER_PAYMENT,
            'payment_plan' => 1,
            'payment_subtotal' => 4300.00,
            'payment_total' => 4300.00,
            'payment_initial' => 3500.00,
        ]);
        $this->assertDatabaseHas('student_payments', [
            'student_id' => $student->id,
            'amount' => 3500.00,
        ]);
        $this->assertSame(800.0, $student->fresh()->load(['payments', 'course'])->balanceDue());
        $this->assertSame(100.0, $student->fresh()->load('course')->suggestedAbonoAmount());
    }

    public function test_user_without_discount_permission_cannot_apply_a_discount(): void
    {
        $role = Role::query()->where('slug', 'recepcionista')->first();
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'discount' => '%10',
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'discount_percent' => 0.00,
            'discount_amount' => 0.00,
            'payment_total' => 3500.00,
        ]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertDontSee('for="student_discount"', false)
            ->assertDontSee('placeholder="%15 o 15.00"', false)
            ->assertDontSee('No tienes permiso para aplicar descuentos.');
    }

    public function test_discount_amount_is_used_when_percent_is_zero(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(4000);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'discount' => '250.00',
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'discount_amount' => 250.00,
            'discount_percent' => 6.25,
            'payment_total' => 3750.00,
        ]);
    }

    public function test_installment_plan_requires_an_initial_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(4000);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'payment_plan' => 3,
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('payment_initial');
    }

    public function test_single_payment_records_the_full_total(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(4000);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'payment_plan' => 1,
                'payment_initial' => 50,
            ]))
            ->assertRedirect(route('admin.students.index'));

        $student = Student::query()->where('email', 'ana.garcia@example.com')->first();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'payment_plan' => 1,
            'payment_initial' => 0.00,
            'payment_total' => 4000.00,
        ]);
        $this->assertDatabaseHas('student_payments', [
            'student_id' => $student->id,
            'amount' => 4000.00,
        ]);
    }

    public function test_per_class_plan_keeps_the_balance_until_each_class_is_paid(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(4000);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'payment_plan' => Student::PAYMENT_PLAN_PER_CLASS,
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $student = Student::query()->where('email', 'ana.garcia@example.com')->first();

        $this->assertNotNull($student);
        $this->assertTrue($student->isPerClassPlan());
        $this->assertSame(500.0, $student->amountPerClass());
        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'payment_plan' => 0,
            'payment_initial' => 0.00,
            'payment_total' => 4000.00,
        ]);
        $this->assertSame(0, $student->payments()->count());
        $this->assertSame(4000.0, $student->balanceDue());

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Pago por clase')
            ->assertSee('$500.00')
            ->assertSee('Debe $4,000.00');
    }

    public function test_per_class_plan_can_record_an_optional_first_class_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(4000);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'payment_plan' => Student::PAYMENT_PLAN_PER_CLASS,
                'payment_initial' => 500,
            ]))
            ->assertRedirect(route('admin.students.index'));

        $student = Student::query()->where('email', 'ana.garcia@example.com')->first();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'payment_plan' => 0,
            'payment_initial' => 500.00,
            'payment_total' => 4000.00,
        ]);
        $this->assertDatabaseHas('student_payments', [
            'student_id' => $student->id,
            'amount' => 500.00,
        ]);
        $this->assertSame(3500.0, $student->fresh()->load('payments')->balanceDue());
    }

    public function test_students_index_shows_home_class_and_payment_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse();
        Student::factory()->create([
            'course_id' => $course->id,
            'name' => 'Ana',
            'last_name' => 'García',
            'is_home_class' => true,
            'home_fee_amount' => 100,
            'home_fee_percent' => 2.86,
            'meeting_point' => 'Esquina del parque, auto blanco',
        ]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('A domicilio')
            ->assertSee('Tarifa envío')
            ->assertSee('Esquina del parque, auto blanco')
            ->assertSee('data-is-home-class="1"', false)
            ->assertSee('data-home-fee="100.00"', false)
            ->assertSee('data-home-fee-mode="total"', false)
            ->assertSee('Cómo se aplica')
            ->assertSee('En cada clase o abono')
            ->assertSee('data-meeting-point="Esquina del parque, auto blanco"', false);
    }

    private function makeCourse(float $cost = 5200): Course
    {
        return Course::query()->create([
            'name' => 'Curso intermedio',
            'description' => 'Prueba',
            'cost' => $cost,
            'temario' => 'Prueba',
            'num_classes' => 8,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'course_id' => $course->id,
            'name' => 'Ana',
            'last_name' => 'García',
            'email' => 'ana.garcia@example.com',
            'phone' => '5512345678',
            'address' => 'Calle 1',
            'city' => 'Ciudad de México',
            'state' => 'CDMX',
            'zip' => '01000',
            'country' => 'México',
            'is_home_class' => 0,
            'discount' => '',
            'payment_method' => Student::PAYMENT_CASH,
            'payment_plan' => 1,
        ], $overrides);
    }
}
