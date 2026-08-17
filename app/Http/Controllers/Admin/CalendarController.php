<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Services\StudentSlotAvailabilityService;
use App\Support\ReservaCalendarColors;
use App\Support\ReservaCalendarLabels;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(
        private readonly StudentSlotAvailabilityService $slots,
    ) {}

    public function events(Request $request): JsonResponse
    {
        $start = Carbon::parse($request->query('start', now()->startOfWeek()))->toDateString();
        $end = Carbon::parse($request->query('end', now()->addWeek()))->toDateString();

        $reservationEvents = Reservas::query()
            ->with(['student', 'instructor', 'vehicle'])
            ->active()
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function (Reservas $reserva) {
                $studentName = $reserva->student
                    ? trim($reserva->student->name.' '.$reserva->student->last_name)
                    : 'Estudiante';
                $instructorName = $reserva->instructor
                    ? trim($reserva->instructor->name.' '.$reserva->instructor->last_name)
                    : 'Instructor';
                $vehicleLabel = $reserva->vehicle
                    ? "{$reserva->vehicle->modelo} ({$reserva->vehicle->plate})"
                    : 'Vehículo';

                $colors = ReservaCalendarColors::forStatus($reserva->status);
                $movable = in_array($reserva->status, ['pendiente', 'confirmada'], true);

                return [
                    'id' => $reserva->id,
                    'title' => "Clase — {$studentName}",
                    'start' => $reserva->startsAt(),
                    'end' => $reserva->endsAt(),
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'textColor' => $colors['text'],
                    'editable' => $movable,
                    'startEditable' => $movable,
                    'durationEditable' => false,
                    'classNames' => array_values(array_filter([
                        $colors['class'],
                        $movable ? 'fc-event-movable' : null,
                    ])),
                    'extendedProps' => [
                        'isAvailable' => false,
                        'student' => $studentName,
                        'instructor' => $instructorName,
                        'vehicle' => $vehicleLabel,
                        'status' => $reserva->status,
                        'date' => $reserva->date,
                        'time' => $reserva->time,
                        'endTime' => date('H:i', strtotime($reserva->endsAt())),
                    ],
                ];
            });

        $availableEvents = collect($this->slots->availableSlotsForRange($start, $end))
            ->map(function (array $slot) {
                $colors = ReservaCalendarColors::forAvailable();
                $startAt = "{$slot['date']} {$slot['time']}:00";
                $endAt = date('Y-m-d H:i:s', strtotime($startAt.' +'.Reservas::CLASS_DURATION_MINUTES.' minutes'));
                return [
                    'id' => "available-{$slot['date']}-{$slot['time']}",
                    'title' => ReservaCalendarLabels::availableEventTitle(
                        ReservaCalendarLabels::cuposEnHorario($slot['free_instructors'], $slot['free_vehicles']),
                        $slot['time']
                    ),
                    'start' => $startAt,
                    'end' => $endAt,
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'textColor' => $colors['text'],
                    'editable' => false,
                    'startEditable' => false,
                    'durationEditable' => false,
                    'classNames' => [$colors['class']],
                    'extendedProps' => [
                        'isAvailable' => true,
                        'date' => $slot['date'],
                        'time' => $slot['time'],
                        'endTime' => date('H:i', strtotime($endAt)),
                        'cupos' => $slot['cupos'],
                        'freeInstructors' => $slot['free_instructors'],
                        'freeVehicles' => $slot['free_vehicles'],
                    ],
                ];
            });

        return response()->json($reservationEvents->concat($availableEvents)->values());
    }
}
