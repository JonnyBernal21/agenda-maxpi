<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Services\ReservaAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaAvailabilityService $availability,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();
        $student->load('course');

        if (! $student->canReserve()) {
            return back()->withErrors([
                'reserva' => 'No tienes clases disponibles en tu curso.',
            ]);
        }

        $timeSlots = Reservas::availableTimes();

        $validated = $request->validate([
            'instructor_id' => ['required', Rule::exists('instructors', 'id')],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', Rule::in($timeSlots)],
        ], [
            'date.required' => 'Debes seleccionar la fecha de la clase.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'time.required' => 'Debes seleccionar la hora de inicio.',
            'time.in' => 'La hora seleccionada no es válida.',
            'instructor_id.required' => 'Debes seleccionar un instructor.',
            'vehicle_id.required' => 'Debes seleccionar un vehículo.',
        ]);

        $check = $this->availability->check(
            $validated['date'],
            $validated['time'],
            (int) $validated['instructor_id'],
            (int) $validated['vehicle_id'],
            (int) $student->id,
        );

        if (! $check['available']) {
            return back()
                ->withInput()
                ->withErrors(['availability' => implode(' ', $check['messages'])]);
        }

        Reservas::query()->create([
            'student_id' => (string) $student->id,
            'instructor_id' => (string) $validated['instructor_id'],
            'vehicle_id' => (string) $validated['vehicle_id'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'status' => 'pendiente',
        ]);

        return redirect()
            ->route('student.dashboard')
            ->with('success', 'Clase reservada correctamente. Te quedan '.$student->fresh()->remainingClasses().' clases.');
    }
}
