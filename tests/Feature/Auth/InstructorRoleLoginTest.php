<?php

namespace Tests\Feature\Auth;

use App\Models\Instructor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorRoleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_role_user_is_redirected_to_instructor_dashboard(): void
    {
        $role = Role::query()->where('slug', Role::INSTRUCTOR)->first();
        $instructor = Instructor::factory()->create([
            'email' => 'laura.ramirez@agenda-maxpi.test',
            'password' => 'password',
        ]);
        User::factory()->create([
            'name' => 'Laura Ramírez',
            'email' => $instructor->email,
            'password' => 'password',
            'role_id' => $role->id,
        ]);

        $this->post('/login', [
            'email' => $instructor->email,
            'password' => 'password',
        ])->assertRedirect(route('instructor.dashboard'));

        $this->assertAuthenticatedAs($instructor, 'instructor');
        $this->assertGuest('web');
    }

    public function test_instructor_login_url_redirects_to_shared_login(): void
    {
        $this->get(route('instructor.login'))->assertRedirect(route('login'));
    }

    public function test_permissions_page_lists_the_instructor_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.permissions'))
            ->assertOk()
            ->assertSee('Instructor');
    }
}
