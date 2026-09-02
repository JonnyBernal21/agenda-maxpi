<?php

namespace Tests\Feature;

use App\Models\Student;
use Database\Seeders\CourseSeeder;
use Database\Seeders\StudentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_seeder_runs_without_an_institution_model(): void
    {
        $this->seed([
            CourseSeeder::class,
            StudentSeeder::class,
        ]);

        $this->assertTrue(Student::query()->where('email', 'alumno@agenda-maxpi.test')->exists());
        $this->assertNull(Student::query()->where('email', 'alumno@agenda-maxpi.test')->value('institution_id'));
    }
}
