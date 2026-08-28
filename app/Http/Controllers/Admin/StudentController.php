<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students = Student::query()
            ->with(['course', 'extraClasses'])
            ->withCount([
                'reservas as completed_classes_count' => fn ($query) => $query->where('status', 'completada'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                $student->remaining_classes = $student->remainingClasses();

                return $student;
            });

        return view('admin.students.index', compact('students'));
    }
}
