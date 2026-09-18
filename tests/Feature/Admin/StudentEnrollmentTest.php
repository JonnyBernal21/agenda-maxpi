<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
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
        ]);

        $student = Student::query()->where('email', 'ana.garcia@example.com')->first();

        $this->assertDatabaseHas('student_payments', [
            'student_id' => $student->id,
            'amount' => 4680.00,
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

    public function test_students_index_shows_home_class_and_payment_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $course = $this->makeCourse();
        Student::factory()->create([
            'course_id' => $course->id,
            'name' => 'Ana',
            'last_name' => 'García',
            'is_home_class' => true,
        ]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('A domicilio')
            ->assertSee('data-is-home-class="1"', false);
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
