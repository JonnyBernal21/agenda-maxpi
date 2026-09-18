<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_settings(): void
    {
        $this->get(route('admin.settings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_settings_users_permissions_and_emails(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Nombre de la empresa')
            ->assertSee('Logo de la empresa')
            ->assertSee('Zona horaria')
            ->assertSee('Dirección de la escuela')
            ->assertSee('Teléfono')
            ->assertSee('Usuarios')
            ->assertSee('Permisos por Rol')
            ->assertSee('Correos');

        $this->get(route('admin.settings.users'))
            ->assertOk()
            ->assertSee('Agregar usuario');

        $this->get(route('admin.settings.permissions'))
            ->assertOk()
            ->assertSee('Permisos —')
            ->assertSee('Admin')
            ->assertSee('Recepcionista');

        $this->get(route('admin.emails.index'))
            ->assertOk()
            ->assertSee('Correos');
    }

    public function test_admin_can_change_the_header_title(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), [
                'company_name' => 'Autoescuela Norte',
                'timezone' => 'America/Mexico_City',
                'currency' => 'MXN',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', [
            'company_name' => 'Autoescuela Norte',
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Autoescuela Norte');
    }

    public function test_admin_can_upload_a_company_logo(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), [
                'company_name' => 'Agenda MaxPi',
                'timezone' => 'America/Mexico_City',
                'currency' => 'MXN',
                'logo' => UploadedFile::fake()->create('logo.png', 40, 'image/png'),
            ])
            ->assertRedirect(route('admin.settings.index'));

        $setting = Setting::query()->first();
        $this->assertNotNull($setting?->logo_path);
        Storage::disk('uploads')->assertExists(ltrim(Str::after($setting->logo_path, 'uploads/'), '/'));
    }

    public function test_settings_form_renders_when_cached_setting_lacks_profile_fields(): void
    {
        $this->actingAs(User::factory()->create());
        Cache::forever(SettingService::CACHE_KEY, new Setting(['company_name' => 'Agenda MaxPi']));

        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Zona horaria')
            ->assertSee('Dirección de la escuela');
    }

    public function test_admin_can_update_school_profile_details(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), [
                'company_name' => 'Agenda MaxPi',
                'timezone' => 'America/Cancun',
                'phone' => '5551234567',
                'email' => 'hola@maxpi.test',
                'address' => 'Av. Insurgentes 100',
                'city' => 'Cancún',
                'state' => 'Quintana Roo',
                'zip' => '77500',
                'country' => 'México',
                'currency' => 'MXN',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', [
            'timezone' => 'America/Cancun',
            'phone' => '5551234567',
            'email' => 'hola@maxpi.test',
            'address' => 'Av. Insurgentes 100',
            'city' => 'Cancún',
        ]);
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $this->actingAs(User::factory()->create());
        $role = Role::query()->where('slug', 'recepcionista')->first();

        $this->from(route('admin.settings.users'))
            ->post(route('admin.users.store'), [
                '_form' => 'user',
                'name' => 'Laura Pérez',
                'email' => 'laura@agenda-maxpi.test',
                'role_id' => $role->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.settings.users'));

        $this->assertDatabaseHas('users', [
            'email' => 'laura@agenda-maxpi.test',
            'role_id' => $role->id,
        ]);
    }

    public function test_user_without_permission_cannot_edit_settings(): void
    {
        $role = Role::query()->where('slug', 'contador')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    protected function tearDown(): void
    {
        app(SettingService::class)->forget();
        parent::tearDown();
    }
}
