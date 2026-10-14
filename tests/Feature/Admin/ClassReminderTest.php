<?php

namespace Tests\Feature\Admin;

use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_build_a_whatsapp_reminder_with_rsvp_link(): void
    {
        $this->actingAs(User::factory()->create());
        $reserva = $this->class();

        $response = $this->postJson(route('admin.reservas.reminder', $reserva))
            ->assertOk()
            ->assertJsonPath('whatsapp', '525511112222');

        $token = $reserva->fresh()->rsvp_token;
        $this->assertNotEmpty($token);
        $response->assertJsonFragment(['confirm_url' => route('reservas.rsvp', $token)]);
        $this->assertStringContainsString('¿Asistirás a tu clase?', $response->json('message'));
        $this->assertStringContainsString(route('reservas.rsvp', $token), $response->json('message'));
    }

    public function test_student_can_confirm_attendance_from_the_reminder_link(): void
    {
        $reserva = $this->class();
        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.reservas.reminder', $reserva));

        $token = $reserva->fresh()->rsvp_token;

        $this->get(route('reservas.rsvp', $token))
            ->assertOk()
            ->assertSee('¿Asistirás a tu clase?')
            ->assertSee('Sí, asistiré')
            ->assertSee('No podré asistir');

        $this->post(route('reservas.rsvp.store', $token), [
            'response' => 'asistira',
        ])->assertRedirect(route('reservas.rsvp', $token));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'rsvp_response' => 'asistira',
            'status' => 'confirmada',
        ]);
    }

    public function test_declining_attendance_does_not_cancel_the_class(): void
    {
        $reserva = $this->class();
        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.reservas.reminder', $reserva));

        $token = $reserva->fresh()->rsvp_token;

        $this->post(route('reservas.rsvp.store', $token), [
            'response' => 'no_asistira',
        ])->assertRedirect(route('reservas.rsvp', $token));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'rsvp_response' => 'no_asistira',
            'status' => 'pendiente',
        ]);
    }

    private function class(): Reservas
    {
        $student = Student::factory()->create([
            'name' => 'Alejandra',
            'last_name' => 'Lopez',
            'phone' => '5511112222',
        ]);

        return Reservas::query()->create([
            'student_id' => (string) $student->id,
            'instructor_id' => (string) Instructor::factory()->create()->id,
            'vehicle_id' => (string) Vehicle::factory()->create(['status' => 'disponible'])->id,
            'date' => '2026-10-21',
            'time' => '07:00',
            'status' => 'pendiente',
        ]);
    }
}
