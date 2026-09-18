<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::query()
            ->withCount('students')
            ->orderBy('num_classes')
            ->orderBy('name')
            ->get();

        return view('admin.courses.index', compact('courses'));
    }
}
