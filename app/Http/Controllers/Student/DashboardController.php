<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $student = Auth::guard('student')->user();
        $student->load('course');

        return view('student.dashboard', [
            'student' => $student,
            'completedClasses' => $student->completedClassesCount(),
            'allowedClasses' => $student->allowedClassesCount(),
            'remainingClasses' => $student->remainingClasses(),
            'canReserve' => $student->canReserve(),
        ]);
    }
}
