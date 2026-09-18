<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Expense;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyBalanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_reports(): void
    {
        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_sees_the_monthly_balance_table_and_chart(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create([
            'course_id' => Course::factory()->create()->id,
        ]);

        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 10631,
            'paid_at' => '2026-05-12',
        ]);

        Expense::factory()->create([
            'amount' => 1749.99,
            'date' => '2026-05-18',
            'concept' => 'Carga de gasolina',
        ]);

        $this->get(route('admin.reports.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Reporte de ventas y gastos por mes')
            ->assertSee('Estadísticas mensuales')
            ->assertSee('Mayo')
            ->assertSee('$10,631.00')
            ->assertSee('$1,749.99')
            ->assertSee('$8,881.01')
            ->assertSee('id="monthlyBalanceChart"', false);
    }

    public function test_income_comes_from_each_student_payment_in_the_selected_year(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create([
            'course_id' => Course::factory()->create()->id,
        ]);

        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 4000,
            'paid_at' => '2026-06-04',
        ]);
        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 4742,
            'paid_at' => '2026-06-20',
        ]);
        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 9999,
            'paid_at' => '2025-06-10',
        ]);

        Expense::factory()->create([
            'amount' => 3850,
            'date' => '2026-06-15',
        ]);

        $this->get(route('admin.reports.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('$8,742.00')
            ->assertSee('$3,850.00')
            ->assertSee('$4,892.00')
            ->assertDontSee('$9,999.00');
    }
}
