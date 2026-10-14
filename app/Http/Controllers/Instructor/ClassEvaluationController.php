<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Services\ClassEvaluationService;
use App\Support\ClassEvaluationCatalog;
use App\Support\ReservaClassNumbers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClassEvaluationController extends Controller
{
    public function __construct(private readonly ClassEvaluationService $evaluations) {}

    public function start(Reservas $reserva): RedirectResponse
    {
        $this->evaluations->start($reserva, Auth::guard('instructor')->user());

        return redirect()->route('instructor.evaluations.edit', $reserva);
    }

    public function edit(Reservas $reserva): View|RedirectResponse
    {
        $evaluation = $this->evaluations->start($reserva, Auth::guard('instructor')->user());
        $reserva->loadMissing(['student.course', 'vehicle', 'instructor']);

        if ($reserva->status === 'completada' && $evaluation->isSubmitted()) {
            return redirect()->route('instructor.evaluations.summary', $reserva);
        }

        return view('instructor.evaluations.form', [
            'reserva' => $reserva,
            'evaluation' => $evaluation,
            'classNumber' => ReservaClassNumbers::mapFor(collect([$reserva]))[(int) $reserva->id] ?? null,
            'groups' => ClassEvaluationCatalog::groups(),
            'scale' => ClassEvaluationCatalog::scale(),
            'scores' => $evaluation->scores ?? [],
            'timeLabel' => \App\Models\Reservas::normalizeTime((string) $reserva->time),
            'lockEvaluation' => true,
        ]);
    }

    public function update(Request $request, Reservas $reserva): RedirectResponse
    {
        $ids = ClassEvaluationCatalog::ids();
        $validated = $request->validate([
            'vehicle_type' => ['required', 'in:manual,automatico'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'scores' => ['required', 'array'],
            'scores.*' => ['required', 'integer', 'between:0,5'],
        ]);

        $missing = array_diff($ids, array_keys($validated['scores']));

        if ($missing !== []) {
            return back()
                ->withErrors(['scores' => 'Califica todos los criterios antes de continuar.'])
                ->withInput();
        }

        $this->evaluations->save($reserva, Auth::guard('instructor')->user(), $validated);

        return redirect()
            ->route('instructor.evaluations.summary', $reserva)
            ->with('success', 'Evaluación guardada. Revisa el resumen antes de enviarla.');
    }

    public function cancel(Reservas $reserva): RedirectResponse
    {
        $this->evaluations->cancel($reserva, Auth::guard('instructor')->user());

        return redirect()
            ->route('instructor.dashboard')
            ->with('success', 'Saliste de la evaluación. La clase no se completó.');
    }

    public function summary(Reservas $reserva): View
    {
        $instructor = Auth::guard('instructor')->user();
        abort_unless((int) $reserva->instructor_id === (int) $instructor->id, 403);

        $reserva->loadMissing(['student.course', 'vehicle', 'instructor', 'evaluation']);
        $evaluation = $reserva->evaluation;

        abort_unless($evaluation?->isSubmitted(), 404);

        return view('instructor.evaluations.summary', [
            'reserva' => $reserva,
            'evaluation' => $evaluation,
            'classNumber' => ReservaClassNumbers::mapFor(collect([$reserva]))[(int) $reserva->id] ?? null,
            'items' => $evaluation->scoredItems(),
            'lockEvaluation' => $reserva->status !== 'completada',
        ]);
    }

    public function send(Reservas $reserva): RedirectResponse
    {
        $sent = $this->evaluations->send($reserva, Auth::guard('instructor')->user());

        return redirect()
            ->route('instructor.dashboard')
            ->with(
                'success',
                $sent
                    ? 'La evaluación se envió al alumno y la clase quedó completada.'
                    : 'La clase quedó completada, pero el alumno no tiene correo para enviar el resultado.',
            );
    }

    public function complete(Reservas $reserva): RedirectResponse
    {
        $this->evaluations->complete($reserva, Auth::guard('instructor')->user());

        return redirect()
            ->route('instructor.dashboard')
            ->with('success', 'La clase quedó marcada como completada.');
    }
}
