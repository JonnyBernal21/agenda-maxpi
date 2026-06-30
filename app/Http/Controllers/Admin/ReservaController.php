<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Models\Student;
use App\Services\ReservaAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaAvailabilityService $availability,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $timeSlots = Reservas::availableTimes();

        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')],
            'instructor_id' => ['required', Rule::exists('instructors', 'id')],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', Rule::in($timeSlots)],
            'status' => ['required', Rule::in(['pendiente', 'confirmada'])],
        ], [
            'student_id.required' => 'Debes buscar y seleccionar un alumno registrado.',
            'student_id.exists' => 'El alumno seleccionado no existe.',
            'date.required' => 'Debes seleccionar la fecha de la clase.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'time.required' => 'Debes seleccionar la hora de inicio.',
            'time.in' => 'La hora seleccionada no es válida.',
            'instructor_id.required' => 'Debes seleccionar un instructor.',
            'vehicle_id.required' => 'Debes seleccionar un vehículo.',
            'status.required' => 'Debes seleccionar el estado de la reserva.',
        ]);

        $student = Student::query()->with('course')->findOrFail($validated['student_id']);

        if (! $student->course) {
            return back()
                ->withInput()
                ->withErrors([
                    'student_id' => 'El alumno no tiene un curso asignado.',
                ]);
        }

        if (! $student->canReserve()) {
            return back()
                ->withInput()
                ->withErrors([
                    'student_id' => "El alumno agotó sus clases del {$student->course->name} ({$student->allowedClassesCount()} clases).",
                ]);
        }

        $check = $this->availability->check(
            $validated['date'],
            $validated['time'],
            (int) $validated['instructor_id'],
            (int) $validated['vehicle_id'],
            (int) $validated['student_id'],
        );

        if (! $check['available']) {
            return back()
                ->withInput()
                ->withErrors(['time' => implode(' ', $check['messages'])]);
        }

        Reservas::query()->create([
            'student_id' => (string) $validated['student_id'],
            'instructor_id' => (string) $validated['instructor_id'],
            'vehicle_id' => (string) $validated['vehicle_id'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Clase agendada para '.trim($student->name.' '.$student->last_name).'. Clases restantes: '.$student->fresh()->remainingClasses().'.');
    }

    public function confirm(Reservas $reserva): JsonResponse
    {
        if ($reserva->status !== 'pendiente') {
            return response()->json([
                'message' => 'Solo se pueden confirmar citas en estado pendiente.',
            ], 422);
        }

        $reserva->update(['status' => 'confirmada']);

        return response()->json([
            'message' => 'Cita confirmada correctamente.',
            'status' => 'confirmada',
        ]);
    }

    public function complete(Reservas $reserva): JsonResponse
    {
        if (! in_array($reserva->status, ['pendiente', 'confirmada'], true)) {
            return response()->json([
                'message' => 'Solo se pueden completar citas pendientes o confirmadas.',
            ], 422);
        }

        $reserva->update(['status' => 'completada']);

        return response()->json([
            'message' => 'Clase marcada como completada.',
            'status' => 'completada',
        ]);
    }
}
