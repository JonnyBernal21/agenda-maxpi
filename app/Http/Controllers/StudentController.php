<?php

namespace App\Http\Controllers;

use App\Models\Reservas;
use App\Models\Student;
use App\Models\StudentExtraClass;
use App\Services\StudentMailService;
use App\Support\ReservaStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentMailService $studentMail,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 4) {
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
            ->limit(8)
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

        $student = Student::query()->create([
            ...$validated,
            'password' => 'password',
        ]);

        $student->load('course');

        $fallback = URL::previous() ?: route('admin.students.index');

        return redirect()
            ->to($fallback)
            ->with('assign_schedule', [
                'student_id' => $student->id,
                'student_name' => $student->fullName(),
                'course_name' => $student->course?->name ?? 'Sin curso',
                'num_classes' => $student->remainingClasses(),
            ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $rawExtras = $request->input('extra_classes', []);
        $request->merge([
            'extra_classes' => collect(is_array($rawExtras) ? $rawExtras : [])
                ->filter(fn ($row) => is_array($row) && filled($row['type'] ?? null))
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('students', 'email')->ignore($student->id)],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'extra_classes' => ['nullable', 'array'],
            'extra_classes.*.type' => ['required', Rule::in(array_keys(StudentExtraClass::TYPES))],
            'extra_classes.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'extra_classes.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $extras = collect($validated['extra_classes'] ?? [])
            ->map(fn (array $row) => [
                'type' => $row['type'],
                'quantity' => (int) $row['quantity'],
                'notes' => filled($row['notes'] ?? null) ? $row['notes'] : null,
            ])
            ->all();

        unset($validated['extra_classes']);

        DB::transaction(function () use ($student, $validated, $extras) {
            $student->update($validated);
            $student->extraClasses()->delete();

            if ($extras !== []) {
                $student->extraClasses()->createMany($extras);
            }
        });

        return redirect()
            ->to(URL::previous() ?: route('admin.students.index'))
            ->with('success', "Se actualizó la información de {$student->fullName()}.");
    }

    public function destroy(Student $student): RedirectResponse
    {
        $name = $student->fullName();
        $student->delete();

        return redirect()
            ->to(URL::previous() ?: route('admin.students.index'))
            ->with('success', "Se eliminó a {$name} de la lista.");
    }

    public function schedule(Student $student): JsonResponse
    {
        $student->load('course');
        $today = now()->toDateString();

        $reservas = $student->reservas()
            ->with(['instructor', 'vehicle'])
            ->orderByRaw('case when date >= ? then 0 else 1 end', [$today])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $allowed = $student->allowedClassesCount();
        $booked = $reservas->where('status', '!=', 'cancelada')->count();

        $classes = $reservas->map(function (Reservas $reserva) use ($today) {
            $date = $reserva->date instanceof \DateTimeInterface
                ? $reserva->date->format('Y-m-d')
                : substr((string) $reserva->date, 0, 10);
            $time = Reservas::normalizeTime((string) $reserva->time);

            return [
                'id' => $reserva->id,
                'date' => $date,
                'time' => $time,
                'end_time' => date('H:i', strtotime($reserva->endsAt())),
                'is_past' => $date < $today,
                'status' => $reserva->status,
                'status_label' => ReservaStatus::label($reserva->status),
                'status_class' => ReservaStatus::badgeClass($reserva->status),
                'instructor' => $reserva->instructor?->fullName() ?: '—',
                'vehicle' => $reserva->vehicle
                    ? trim($reserva->vehicle->modelo.' ('.$reserva->vehicle->plate.')')
                    : '—',
            ];
        });

        return response()->json([
            'id' => $student->id,
            'name' => $student->fullName(),
            'course' => $student->course?->name,
            'allowed' => $allowed,
            'booked' => $booked,
            'remaining' => max(0, $allowed - $booked),
            'classes' => $classes,
        ]);
    }

    public function sendSchedule(Student $student): JsonResponse
    {
        $sent = $this->studentMail->sendSchedule($student);

        if (! $sent) {
            $hasClasses = $student->reservas()->where('status', '!=', 'cancelada')->exists();

            return response()->json([
                'message' => $hasClasses
                    ? 'No se pudieron enviar los horarios por correo. Intenta de nuevo.'
                    : 'El alumno no tiene horarios asignados para enviar.',
            ], $hasClasses ? 500 : 422);
        }

        return response()->json([
            'message' => "Registro exitoso. Horarios enviados por correo a {$student->email}.",
            'email' => $student->email,
        ]);
    }
}
