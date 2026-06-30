<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $students = Student::query()
            ->with('course')
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'last_name' => $student->last_name,
                'full_name' => trim($student->name.' '.$student->last_name),
                'email' => $student->email,
                'phone' => $student->phone,
                'course' => $student->course?->name,
                'allowed_classes' => $student->allowedClassesCount(),
                'completed_classes' => $student->completedClassesCount(),
                'used_classes' => $student->completedClassesCount(),
                'remaining_classes' => $student->remainingClasses(),
                'can_reserve' => $student->canReserve(),
            ]);

        return response()->json($students);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:students,email'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
        ]);

        Student::query()->create([
            ...$validated,
            'password' => 'password',
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Alumno registrado correctamente. Contraseña inicial: password');
    }
}
