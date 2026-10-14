<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_instructor_creates_a_login_user(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.instructors.index'))
            ->post(route('admin.instructors.store'), $this->payload([
                'email' => 'gerarod@gmail.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ]))
            ->assertRedirect(route('admin.instructors.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('instructors', [
            'email' => 'gerarod@gmail.com',
            'name' => 'GERARDO',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'gerarod@gmail.com',
            'name' => 'GERARDO BERNAL ROSSANO',
            'role_id' => Role::query()->where('slug', Role::INSTRUCTOR)->value('id'),
        ]);
    }

    public function test_instructor_logs_in_from_the_shared_login(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.instructors.store'), $this->payload([
            'email' => 'gerarod@gmail.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]))->assertSessionHasNoErrors();

        $this->post('/logout');

        $instructor = Instructor::query()->where('email', 'gerarod@gmail.com')->first();

        $this->post('/login', [
            'email' => 'gerarod@gmail.com',
            'password' => 'secret123',
        ])->assertRedirect(route('instructor.dashboard'));

        $this->assertAuthenticatedAs($instructor, 'instructor');
        $this->assertGuest('web');
    }

    public function test_instructor_portal_login_redirects_to_shared_login(): void
    {
        $this->get('/instructor/login')->assertRedirect(route('login'));
        $this->post('/instructor/login')->assertRedirect(route('login'));
    }

    public function test_legacy_instructor_without_user_can_use_shared_login(): void
    {
        $instructor = Instructor::factory()->create([
            'email' => 'solo.instructor@agenda-maxpi.test',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $instructor->email,
            'password' => 'password',
        ])->assertRedirect(route('instructor.dashboard'));

        $this->assertAuthenticatedAs($instructor, 'instructor');
    }

    public function test_cannot_register_an_instructor_with_an_admin_email(): void
    {
        Storage::fake('uploads');
        $admin = User::factory()->create(['email' => 'admin@agenda-maxpi.test']);
        $this->actingAs($admin);

        $this->from(route('admin.instructors.index'))
            ->post(route('admin.instructors.store'), $this->payload([
                'email' => 'admin@agenda-maxpi.test',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ]))
            ->assertRedirect(route('admin.instructors.index'))
            ->assertSessionHasErrors('email');
    }

    public function test_deleting_an_instructor_removes_the_login_user(): void
    {
        $role = Role::query()->where('slug', Role::INSTRUCTOR)->first();
        $instructor = Instructor::factory()->create([
            'email' => 'borrar@agenda-maxpi.test',
        ]);
        $user = User::factory()->create([
            'email' => $instructor->email,
            'role_id' => $role->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('admin.instructors.index'))
            ->delete(route('admin.instructors.destroy', $instructor))
            ->assertRedirect(route('admin.instructors.index'));

        $this->assertSoftDeleted($instructor);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_instructor_accounts_are_not_listed_in_settings_users(): void
    {
        $role = Role::query()->where('slug', Role::INSTRUCTOR)->first();
        User::factory()->create([
            'name' => 'Gerardo Instructor',
            'email' => 'gerarod@gmail.com',
            'role_id' => $role->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.users'))
            ->assertOk()
            ->assertDontSee('gerarod@gmail.com')
            ->assertDontSee('Gerardo Instructor');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            '_form' => 'instructor',
            'name' => 'Gerardo',
            'last_name' => 'Bernal Rossano',
            'email' => 'instructor@agenda-maxpi.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '71231991212',
            'address' => 'Calle 1',
            'city' => 'Toluca',
            'state' => 'México',
            'zip' => '50000',
            'country' => 'México',
            'photo' => UploadedFile::fake()->create('foto.jpg', 120, 'image/jpeg'),
            'dni_front' => UploadedFile::fake()->create('dni-frente.jpg', 120, 'image/jpeg'),
            'dni_back' => UploadedFile::fake()->create('dni-reverso.jpg', 120, 'image/jpeg'),
            'address_proof' => UploadedFile::fake()->create('comprobante.pdf', 80, 'application/pdf'),
        ], $overrides);
    }
}
