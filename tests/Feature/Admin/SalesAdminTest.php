<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_sales(): void
    {
        $this->get(route('admin.sales.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_sales(): void
    {
        $role = Role::query()->where('slug', 'recepcionista')->first();
        $role->permissions()->sync(
            Permission::query()->whereIn('key', ['students.manage'])->pluck('id')
        );

        $this->actingAs(User::factory()->create(['role_id' => $role->id]))
            ->get(route('admin.sales.index'))
            ->assertForbidden();
    }

    public function test_accountant_can_view_sales(): void
    {
        $role = Role::query()->where('slug', 'contador')->first();

        $this->actingAs(User::factory()->create(['role_id' => $role->id]))
            ->get(route('admin.sales.index'))
            ->assertOk()
            ->assertSee('Ingresos')
            ->assertSee('Registro de ingresos')
            ->assertDontSee('Agregar venta')
            ->assertDontSee('Agregar ingreso')
            ->assertDontSee('Registró alumno');
    }

    public function test_admin_sees_full_payments_and_installments_with_authors(): void
    {
        $this->travelTo('2026-09-21 14:30:00');
        $enroller = User::factory()->create(['name' => 'Laura Inscripcion']);
        $collector = User::factory()->create(['name' => 'Mario Cobranza']);
        $course = Course::factory()->create(['name' => 'Curso intensivo']);

        $fullStudent = Student::factory()->create([
            'course_id' => $course->id,
            'name' => 'Ana',
            'last_name' => 'Garcia',
            'created_by' => $enroller->id,
            'payment_total' => 3500,
            'payment_plan' => 1,
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);
        StudentPayment::factory()->create([
            'student_id' => $fullStudent->id,
            'created_by' => $enroller->id,
            'amount' => 3500,
            'paid_at' => '2026-09-21',
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);

        $planStudent = Student::factory()->create([
            'course_id' => $course->id,
            'name' => 'Roberto',
            'last_name' => 'Valdez',
            'created_by' => $enroller->id,
            'payment_total' => 4000,
            'payment_plan' => 2,
            'payment_initial' => 1000,
            'payment_method' => Student::PAYMENT_CASH,
        ]);
        StudentPayment::factory()->create([
            'student_id' => $planStudent->id,
            'created_by' => $enroller->id,
            'amount' => 1000,
            'paid_at' => '2026-09-21',
            'payment_method' => Student::PAYMENT_CASH,
        ]);
        StudentPayment::factory()->create([
            'student_id' => $planStudent->id,
            'created_by' => $collector->id,
            'amount' => 500,
            'paid_at' => '2026-09-21',
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.sales.index'))
            ->assertOk()
            ->assertSee('3 ingresos en total')
            ->assertSee('ANA GARCIA')
            ->assertSee('ROBERTO VALDEZ')
            ->assertSee('Pago completo · Curso intensivo')
            ->assertSee('Abono · Curso intensivo')
            ->assertSee('$3,500.00')
            ->assertSee('$1,000.00')
            ->assertSee('$500.00')
            ->assertSee('Transferencia')
            ->assertSee('Efectivo')
            ->assertSee('Laura Inscripcion')
            ->assertSee('Mario Cobranza')
            ->assertSee('21/09/2026')
            ->assertSeeInOrder([
                'Total del día',
                '$5,000.00',
                'Pago completo',
                '$3,500.00',
                'Abonos',
                '$1,500.00',
                'Cliente',
                'Concepto',
                'Método de pago',
                'Monto',
                'Registrado por',
                'Fecha y hora',
            ])
            ->assertDontSee('Registró alumno')
            ->assertDontSee('Agregar venta')
            ->assertDontSee('Agregar ingreso');
    }

    public function test_sales_list_can_be_filtered_by_date_range(): void
    {
        $this->travelTo('2026-09-21 14:30:00');
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create([
            'course_id' => Course::factory()->create()->id,
            'payment_total' => 4000,
        ]);

        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 1111,
            'paid_at' => '2026-09-20',
            'created_at' => '2026-09-20 10:00:00',
        ]);
        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 2222,
            'paid_at' => '2026-09-21',
            'created_at' => '2026-09-21 12:00:00',
        ]);

        $this->get(route('admin.sales.index', ['from' => '2026-09-21', 'to' => '2026-09-21']))
            ->assertOk()
            ->assertSee('$2,222.00')
            ->assertDontSee('$1,111.00')
            ->assertSee('1 ingreso en total');

        $this->get(route('admin.sales.index', ['from' => '2026-09-20', 'to' => '2026-09-21']))
            ->assertOk()
            ->assertSee('$1,111.00')
            ->assertSee('$2,222.00')
            ->assertSee('2 ingresos en total');
    }

    public function test_registering_a_student_stores_the_author_on_the_sale(): void
    {
        $admin = User::factory()->create(['name' => 'Carla Admin']);
        $course = Course::factory()->create(['cost' => 3500, 'num_classes' => 10]);

        $this->actingAs($admin)
            ->from(route('admin.students.index'))
            ->post(route('admin.students.store'), [
                'course_id' => $course->id,
                'name' => 'Elena',
                'last_name' => 'Ruiz',
                'email' => 'elena.ruiz@example.com',
                'phone' => '5512345678',
                'address' => 'Calle 1',
                'city' => 'CDMX',
                'state' => 'CDMX',
                'zip' => '01000',
                'country' => 'México',
                'is_home_class' => 0,
                'payment_method' => Student::PAYMENT_CASH,
                'payment_plan' => 1,
            ])
            ->assertRedirect(route('admin.students.index'));

        $student = Student::query()->where('email', 'elena.ruiz@example.com')->first();

        $this->assertNotNull($student);
        $this->assertSame($admin->id, $student->created_by);
        $this->assertDatabaseHas('student_payments', [
            'student_id' => $student->id,
            'created_by' => $admin->id,
            'amount' => 3500.00,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sales.index'))
            ->assertOk()
            ->assertSee('ELENA RUIZ')
            ->assertSee('Pago completo')
            ->assertSee('Carla Admin');
    }
}
