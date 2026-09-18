<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Expense;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Services\MonthlyBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_groups_sales_and_expenses_for_every_month(): void
    {
        $student = Student::factory()->create([
            'course_id' => Course::factory()->create()->id,
        ]);

        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 10631,
            'paid_at' => '2026-05-10',
        ]);
        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 8742,
            'paid_at' => '2026-06-02',
        ]);

        Expense::factory()->create([
            'amount' => 1749.99,
            'date' => '2026-05-12',
        ]);
        Expense::factory()->create([
            'amount' => 3850,
            'date' => '2026-06-08',
        ]);

        $report = (new MonthlyBalanceService)->forYear(2026);
        $may = $report['months'][4];
        $june = $report['months'][5];
        $january = $report['months'][0];

        $this->assertSame(2026, $report['year']);
        $this->assertCount(12, $report['months']);
        $this->assertSame('Mayo', $may['name']);
        $this->assertEquals(10631.0, $may['sales']);
        $this->assertEquals(1749.99, $may['expenses']);
        $this->assertEquals(8881.01, $may['balance']);
        $this->assertEquals(8742.0, $june['sales']);
        $this->assertEquals(3850.0, $june['expenses']);
        $this->assertEquals(4892.0, $june['balance']);
        $this->assertEquals(0.0, $january['sales']);
        $this->assertEquals(0.0, $january['expenses']);
        $this->assertSame($report['chart']['sales'][4], $may['sales']);
        $this->assertSame($report['chart']['expenses'][5], $june['expenses']);
        $this->assertSame('May', $report['chart']['labels'][4]);
        $this->assertCount(12, $report['chart']['labels']);
    }
}
