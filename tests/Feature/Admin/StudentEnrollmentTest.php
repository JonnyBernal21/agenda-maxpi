<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\SettingService;
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

    public function test_home_class_adds_a_one_hundred_fee(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
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

    public function test_school_class_ignores_meeting_notes(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 0,
                'meeting_point' => 'No debería guardarse',
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 0,
            'meeting_point' => null,
        ]);
    }

    public function test_home_class_fee_is_included_before_percent_discount(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse(5200);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
                'discount' => '%10',
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
            'payment_subtotal' => 5300.00,
            'discount_percent' => 10.00,
            'discount_amount' => 530.00,
            'payment_total' => 4770.00,
        ]);
    }

    public function test_home_class_uses_the_configured_fee(): void
    {
        $this->actingAs(User::factory()->create());
        Setting::query()->first()?->update(['home_class_fee' => 250]);
        app(SettingService::class)->forget();
        $course = $this->makeCourse(3500);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), $this->payload($course, [
                'is_home_class' => 1,
            ]))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'email' => 'ana.garcia@example.com',
            'is_home_class' => 1,
            'payment_subtotal' => 3750.00,
            'payment_total' => 3750.00,
        ]);
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

    public function test_students_index_shows_home_class_and_payment_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse();
        Student::factory()->create([
            'course_id' => $course->id,
            'name' => 'Ana',
            'last_name' => 'García',
            'is_home_class' => true,
            'meeting_point' => 'Esquina del parque, auto blanco',
        ]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('A domicilio')
            ->assertSee('Esquina del parque, auto blanco')
            ->assertSee('data-is-home-class="1"', false)
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
