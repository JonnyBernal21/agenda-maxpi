<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_quick_actions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Agregar alumno')
            ->assertSee('Agregar instructor')
            ->assertSee('Agregar vehículo')
            ->assertSee('Agregar curso')
            ->assertSee('Agregar gasto')
            ->assertSee('Agendar clase');
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
