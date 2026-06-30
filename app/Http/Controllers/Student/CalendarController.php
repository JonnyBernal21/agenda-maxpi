<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Services\ReservaAvailabilityService;
use App\Services\StudentSlotAvailabilityService;
use App\Support\ReservaCalendarColors;
use App\Support\ReservaCalendarLabels;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function __construct(
        private readonly StudentSlotAvailabilityService $slots,
        private readonly ReservaAvailabilityService $availability,
    ) {}

    public function events(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $studentId = (string) $student->id;

        $start = Carbon::parse($request->query('start', now()->startOfWeek()))->toDateString();
        $end = Carbon::parse($request->query('end', now()->addWeek()))->toDateString();

        $myReservas = Reservas::query()
            ->with(['instructor', 'vehicle'])
            ->active()
            ->where('student_id', $studentId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $myBusyKeys = $myReservas
            ->map(fn (Reservas $r) => "{$r->date}|{$r->time}")
            ->flip();

        $myEvents = $myReservas->map(fn (Reservas $reserva) => $this->mapMyReservation($reserva));

        $availableEvents = collect($this->slots->availableSlotsForRange($start, $end))
            ->reject(function (array $slot) use ($myBusyKeys, $student) {
                if (isset($myBusyKeys["{$slot['date']}|{$slot['time']}"])) {
                    return true;
                }

                return ! $this->availability->studentCanBookSlot((int) $student->id, $slot['date'], $slot['time']);
            })
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
                    'classNames' => [$colors['class']],
                    'extendedProps' => [
                        'isMine' => false,
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

        return response()->json($myEvents->concat($availableEvents)->values());
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMyReservation(Reservas $reserva): array
    {
        $instructorName = $reserva->instructor
            ? trim($reserva->instructor->name.' '.$reserva->instructor->last_name)
            : 'Instructor';
        $vehicleLabel = $reserva->vehicle
            ? "{$reserva->vehicle->modelo} ({$reserva->vehicle->plate})"
            : 'Vehículo';

        $colors = ReservaCalendarColors::forStatus($reserva->status);

        return [
            'id' => $reserva->id,
            'title' => "Mi clase — {$instructorName}",
            'start' => $reserva->startsAt(),
            'end' => $reserva->endsAt(),
            'backgroundColor' => $colors['background'],
            'borderColor' => $colors['border'],
            'textColor' => $colors['text'],
            'classNames' => [$colors['class']],
            'extendedProps' => [
                'isMine' => true,
                'isAvailable' => false,
                'instructor' => $instructorName,
                'vehicle' => $vehicleLabel,
                'status' => $reserva->status,
                'date' => $reserva->date,
                'time' => $reserva->time,
                'endTime' => date('H:i', strtotime($reserva->endsAt())),
            ],
        ];
    }
}
