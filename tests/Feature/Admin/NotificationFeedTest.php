<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_the_notifications_bell_in_the_header(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-notifications', false)
            ->assertSee('Notificaciones');
    }

    public function test_user_without_student_or_sales_permission_does_not_see_notifications(): void
    {
        $role = Role::query()->where('slug', 'contador')->first();
        $role->permissions()->sync(
            Permission::query()->where('key', 'emails.view')->pluck('id')
        );

        $this->actingAs(User::factory()->create(['role_id' => $role->id]))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-notifications', false);
    }

    public function test_feed_includes_new_student_payment_and_pending_balance(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'name' => 'Roberto',
            'last_name' => 'Valdez',
            'phone' => '5512345678',
            'payment_total' => 4000,
            'payment_plan' => 2,
            'payment_initial' => 1000,
        ]);
        $student->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->getJson(route('admin.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread', 3)
            ->assertJsonFragment(['title' => 'Nuevo alumno'])
            ->assertJsonFragment(['title' => 'Abono registrado'])
            ->assertJsonFragment(['title' => 'Cuotas pendientes'])
            ->assertSee('ROBERTO VALDEZ', false);
    }

    public function test_admin_can_mark_all_notifications_as_read(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'payment_total' => 3500,
            'payment_plan' => 1,
        ]);
        $student->payments()->create([
            'amount' => 500,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $this->postJson(route('admin.notifications.read'), ['all' => true])
            ->assertOk()
            ->assertJsonPath('unread', 0);
    }

    public function test_receptionist_can_read_the_notification_feed(): void
    {
        $role = Role::query()->where('slug', 'recepcionista')->first();
        $role->permissions()->sync(
            Permission::query()->whereIn('key', ['students.manage', 'reservas.manage'])->pluck('id')
        );

        $this->actingAs(User::factory()->create(['role_id' => $role->id]))
            ->getJson(route('admin.notifications.index'))
            ->assertOk();
    }

    public function test_guest_cannot_read_notifications(): void
    {
        $this->getJson(route('admin.notifications.index'))
            ->assertUnauthorized();
    }

    public function test_admin_can_delete_a_notification(): void
    {
        $this->actingAs(User::factory()->create());
        $this->makeStudent(['name' => 'Karla']);

        $payload = $this->getJson(route('admin.notifications.index'))
            ->assertOk()
            ->json();

        $key = collect($payload['items'])->firstWhere('type', 'student_registered')['key'];

        $this->postJson(route('admin.notifications.destroy'), ['keys' => [$key]])
            ->assertOk()
            ->assertJsonMissing(['key' => $key]);
    }

    public function test_admin_can_delete_read_notifications(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'payment_total' => 3500,
            'payment_plan' => 1,
        ]);
        $student->payments()->create([
            'amount' => 500,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_CASH,
        ]);

        $payload = $this->getJson(route('admin.notifications.index'))->json();
        $studentKey = collect($payload['items'])->firstWhere('type', 'student_registered')['key'];

        $this->postJson(route('admin.notifications.read'), ['keys' => [$studentKey]])->assertOk();

        $after = $this->postJson(route('admin.notifications.destroy'), ['read' => true])
            ->assertOk()
            ->json();

        $this->assertFalse(collect($after['items'])->contains('key', $studentKey));
        $this->assertTrue(collect($after['items'])->contains('type', 'payment'));
        $this->assertGreaterThan(0, $after['unread']);
    }

    public function test_read_notifications_expire_after_timeout(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create());
        $this->makeStudent(['name' => 'Elena', 'payment_total' => 0]);

        $this->postJson(route('admin.notifications.read'), ['all' => true])
            ->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonFragment(['title' => 'Nuevo alumno']);

        $this->travel(25)->hours();

        $this->getJson(route('admin.notifications.index'))
            ->assertOk()
            ->assertJsonMissing(['title' => 'Nuevo alumno']);
    }

    public function test_liquidated_student_is_not_in_the_daily_reminder(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->makeStudent([
            'name' => 'Marisela',
            'payment_total' => 3500,
            'payment_plan' => 1,
        ]);
        $student->payments()->create([
            'amount' => 3500,
            'paid_at' => now()->toDateString(),
            'payment_method' => Student::PAYMENT_TRANSFER,
        ]);

        $payload = $this->getJson(route('admin.notifications.index'))
            ->assertOk()
            ->json();

        $reminder = collect($payload['items'])->firstWhere('type', 'balance_due');

        $this->assertNull($reminder);
        $this->assertTrue(collect($payload['items'])->contains('title', 'Pago completo'));
        $this->assertTrue(collect($payload['items'])->contains('title', 'Nuevo alumno'));
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
}
