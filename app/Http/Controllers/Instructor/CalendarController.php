<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Support\ReservaCalendarColors;
use App\Support\ReservaCalendarLabels;
use App\Support\ReservaClassNumbers;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $instructor = Auth::guard('instructor')->user();

        $start = Carbon::parse($request->query('start', now()->startOfWeek()))->toDateString();
        $end = Carbon::parse($request->query('end', now()->addWeek()))->toDateString();

        $reservas = Reservas::query()
            ->with(['student', 'vehicle', 'evaluation'])
            ->where('instructor_id', $instructor->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $classNumbers = ReservaClassNumbers::mapFor($reservas);

        $events = $reservas
            ->map(function (Reservas $reserva) use ($classNumbers) {
                $studentName = $reserva->student
                    ? trim($reserva->student->name.' '.$reserva->student->last_name)
                    : 'Alumno';
                $vehicleLabel = $reserva->vehicle
                    ? "{$reserva->vehicle->modelo} ({$reserva->vehicle->plate})"
                    : 'Vehículo';
                $vehicleType = $reserva->vehicle?->typeLabel() ?: null;

                $colors = ReservaCalendarColors::forStatus($reserva->status);
                $cancelled = $reserva->status === 'cancelada';
                $classNumber = $classNumbers[(int) $reserva->id] ?? null;
                $isHomeClass = (bool) $reserva->student?->is_home_class;

                return [
                    'id' => $reserva->id,
                    'title' => ReservaCalendarLabels::bookedEventTitle($studentName, $classNumber, $cancelled, $isHomeClass),
                    'start' => $reserva->startsAt(),
                    'end' => $reserva->endsAt(),
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'textColor' => $colors['text'],
                    'classNames' => [$colors['class']],
                    'extendedProps' => [
                        'student' => $studentName,
                        'vehicle' => $vehicleLabel,
                        'vehicleType' => $vehicleType,
                        'classNumber' => $classNumber,
                        'isHomeClass' => $isHomeClass,
                        ...($reserva->student?->calendarHomeProps() ?? [
                            'isHomeClass' => false,
                            'meetingPoint' => null,
                            'meetingLat' => null,
                            'meetingLng' => null,
                            'notes' => null,
                        ]),
                        'status' => $reserva->status,
                        'rsvp' => $reserva->rsvp_response,
                        'rsvpLabel' => $reserva->rsvpLabel(),
                        'date' => $reserva->date,
                        'time' => $reserva->time,
                        'endTime' => date('H:i', strtotime($reserva->endsAt())),
                        'canStart' => in_array($reserva->status, ['pendiente', 'confirmada'], true),
                        'canContinue' => $reserva->status === 'en_curso',
                        'canViewEvaluation' => $reserva->status === 'completada' && $reserva->evaluation?->isSubmitted(),
                        'startUrl' => in_array($reserva->status, ['pendiente', 'confirmada'], true)
                            ? route('instructor.evaluations.start', $reserva)
                            : null,
                        'formUrl' => in_array($reserva->status, ['pendiente', 'confirmada', 'en_curso'], true)
                            ? route('instructor.evaluations.edit', $reserva)
                            : null,
                        'summaryUrl' => $reserva->status === 'completada'
                            ? route('instructor.evaluations.summary', $reserva)
                            : null,
                    ],
                ];
            });

        return response()->json($events->values());
    }
}
