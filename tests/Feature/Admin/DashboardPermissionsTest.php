<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Permission;
use App\Models\Reservas;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_greeting_follows_time_of_day(): void
    {
        $user = User::factory()->create(['name' => 'Juan Manuel']);

        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'America/Mexico_City'));
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Buenos días, Juan Manuel')
            ->assertSee('bi-sun-fill', false);

        $this->travelTo(Carbon::parse('2026-10-13 15:30:00', 'America/Mexico_City'));
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee('Buenas tardes, Juan Manuel')
            ->assertSee('bi-sunset-fill', false);

        $this->travelTo(Carbon::parse('2026-10-13 21:00:00', 'America/Mexico_City'));
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee('Buenas noches, Juan Manuel')
            ->assertSee('bi-moon-stars-fill', false);
    }

    public function test_dashboard_stats_show_today_classes_and_income(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 15:00:00', 'America/Mexico_City'));

        $instructor = Instructor::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $student = Student::factory()->create();

        Reservas::query()->create([
            'student_id' => (string) $student->id,
            'instructor_id' => (string) $instructor->id,
            'vehicle_id' => (string) $vehicle->id,
            'date' => '2026-10-13',
            'time' => '09:00',
            'status' => 'confirmada',
        ]);
        Reservas::query()->create([
            'student_id' => (string) $student->id,
            'instructor_id' => (string) $instructor->id,
            'vehicle_id' => (string) $vehicle->id,
            'date' => '2026-10-13',
            'time' => '11:00',
            'status' => 'cancelada',
        ]);
        Reservas::query()->create([
            'student_id' => (string) $student->id,
            'instructor_id' => (string) $instructor->id,
            'vehicle_id' => (string) $vehicle->id,
            'date' => '2026-10-14',
            'time' => '09:00',
            'status' => 'pendiente',
        ]);

        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 1500,
            'paid_at' => '2026-10-13',
        ]);
        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 999,
            'paid_at' => '2026-10-12',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Clases de hoy')
            ->assertSee('Ingresos del día')
            ->assertSee('Instructores')
            ->assertSee('Vehículos')
            ->assertSee('$1,500.00')
            ->assertDontSee('$999.00')
            ->assertSee('>1</p>', false);
    }

    public function test_admin_layout_renders_collapsible_sidebar(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="appSidebar"', false)
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('app-topbar', false)
            ->assertSee('>Panel</span>', false)
            ->assertSee('>Alumnos</span>', false);
    }

    public function test_admin_sees_all_quick_actions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Acciones rápidas')
            ->assertSee('Próximos pagos')
            ->assertSee('Agregar alumno')
            ->assertSee('Agregar instructor')
            ->assertSee('Agregar vehículo')
            ->assertSee('Agregar curso')
            ->assertSee('Agregar gasto')
            ->assertSee('Agendar clase')
            ->assertSee('Nuevo alumno')
            ->assertSee('js-open-add-student', false);
    }

    public function test_quick_actions_only_show_permitted_modules(): void
    {
        $role = Role::query()->where('slug', 'recepcionista')->first();
        $role->permissions()->sync(
            Permission::query()
                ->whereIn('key', ['students.manage', 'reservas.manage'])
                ->pluck('id')
        );

        $this->actingAs(User::factory()->create(['role_id' => $role->id]))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Agregar alumno')
            ->assertSee('Agendar clase')
            ->assertSee('aria-label="Nuevo alumno"', false)
            ->assertDontSee('Agregar instructor')
            ->assertDontSee('Agregar vehículo')
            ->assertDontSee('Agregar curso')
            ->assertDontSee('Agregar gasto')
            ->assertDontSee('id="addInstructorModal"', false)
            ->assertDontSee('id="addVehicleModal"', false)
            ->assertDontSee('id="addCourseModal"', false)
            ->assertDontSee('id="addExpenseModal"', false);
    }
}
