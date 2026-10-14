<?php

namespace App\Services;

use App\Mail\StudentClassEvaluationMail;
use App\Models\ClassEvaluation;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Support\ClassEvaluationCatalog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ClassEvaluationService
{
    public function start(Reservas $reserva, Instructor $instructor): ClassEvaluation
    {
        $this->assertOwned($reserva, $instructor);
        $reserva->loadMissing(['evaluation', 'vehicle']);

        if (in_array($reserva->status, ['cancelada', 'completada'], true) && ! $reserva->evaluation?->isSubmitted()) {
            throw ValidationException::withMessages([
                'reserva' => 'Esta clase no se puede iniciar.',
            ]);
        }

        if (in_array($reserva->status, ['pendiente', 'confirmada'], true)) {
            session(["evaluation_return.{$reserva->id}" => $reserva->status]);
            $reserva->update(['status' => 'en_curso']);
        }

        $reserva->loadMissing('vehicle');

        return ClassEvaluation::query()->firstOrCreate(
            ['reserva_id' => $reserva->id],
            [
                'vehicle_type' => $reserva->vehicle?->type,
                'vehicle_label' => $reserva->vehicle?->optionLabel(),
                'started_at' => now(),
            ],
        );
    }

    /**
     * @param  array{vehicle_type: string, notes?: ?string, scores: array<string, int>}  $payload
     */
    public function save(Reservas $reserva, Instructor $instructor, array $payload): ClassEvaluation
    {
        $evaluation = $this->start($reserva, $instructor);
        $ids = ClassEvaluationCatalog::ids();
        $scores = [];

        foreach ($ids as $id) {
            $scores[$id] = max(0, min(5, (int) ($payload['scores'][$id] ?? 0)));
        }

        $average = round(array_sum($scores) / max(1, count($scores)), 1);

        $evaluation->fill([
            'vehicle_type' => $payload['vehicle_type'],
            'vehicle_label' => $reserva->vehicle?->optionLabel() ?: $evaluation->vehicle_label,
            'scores' => $scores,
            'average' => $average,
            'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
            'submitted_at' => now(),
        ])->save();

        return $evaluation->fresh();
    }

    public function cancel(Reservas $reserva, Instructor $instructor): void
    {
        $this->assertOwned($reserva, $instructor);

        if ($reserva->status === 'completada') {
            return;
        }

        $previous = session("evaluation_return.{$reserva->id}");
        session()->forget("evaluation_return.{$reserva->id}");

        if (! in_array($previous, ['pendiente', 'confirmada'], true)) {
            $previous = 'confirmada';
        }

        if ($reserva->status === 'en_curso') {
            $reserva->update(['status' => $previous]);
        }

        $evaluation = $reserva->evaluation()->first();

        if ($evaluation && ! $evaluation->isSubmitted()) {
            $evaluation->delete();
        }
    }

    public function complete(Reservas $reserva, Instructor $instructor): Reservas
    {
        $this->assertOwned($reserva, $instructor);
        $evaluation = $reserva->evaluation;

        if (! $evaluation?->isSubmitted()) {
            throw ValidationException::withMessages([
                'reserva' => 'Primero termina la evaluación del alumno.',
            ]);
        }

        if ($reserva->status !== 'completada') {
            $reserva->update(['status' => 'completada']);
        }

        session()->forget("evaluation_return.{$reserva->id}");

        return $reserva->fresh(['evaluation', 'student', 'vehicle', 'instructor']);
    }

    public function send(Reservas $reserva, Instructor $instructor): bool
    {
        $evaluation = $this->complete($reserva, $instructor)->evaluation;
        $student = $reserva->student;
        $email = trim((string) $student?->email);

        if ($email === '' || ! $evaluation) {
            return false;
        }

        Mail::to($email)->send(new StudentClassEvaluationMail($student, $reserva->fresh(['vehicle', 'instructor']), $evaluation));

        $evaluation->update(['emailed_at' => now()]);

        return true;
    }

    private function assertOwned(Reservas $reserva, Instructor $instructor): void
    {
        if ((int) $reserva->instructor_id !== (int) $instructor->id) {
            abort(403, 'Esta clase no está asignada a ti.');
        }
    }
}
