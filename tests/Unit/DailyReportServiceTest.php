<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Expense;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Services\DailyReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_sales_and_expenses_for_the_selected_day(): void
    {
        $student = Student::factory()->create([
            'course_id' => Course::factory()->create()->id,
        ]);

        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 1500,
            'paid_at' => '2026-09-21',
        ]);
        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 375,
            'paid_at' => '2026-09-21',
        ]);
        StudentPayment::factory()->create([
            'student_id' => $student->id,
            'amount' => 9999,
            'paid_at' => '2026-09-20',
        ]);

        Expense::factory()->create([
            'amount' => 420,
            'date' => '2026-09-21',
        ]);
        Expense::factory()->create([
            'amount' => 800,
            'date' => '2026-09-20',
        ]);

        $report = (new DailyReportService)->forDate(Carbon::parse('2026-09-21'));

        $this->assertEquals(1875.0, $report['sales']['total']);
        $this->assertSame(2, $report['sales']['count']);
        $this->assertEquals(420.0, $report['expenses']['total']);
        $this->assertSame(1, $report['expenses']['count']);
    }
}
