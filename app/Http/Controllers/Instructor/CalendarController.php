<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Support\ReservaCalendarColors;
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

        $events = Reservas::query()
            ->with(['student', 'vehicle'])
            ->active()
            ->where('instructor_id', $instructor->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function (Reservas $reserva) {
                $studentName = $reserva->student
                    ? trim($reserva->student->name.' '.$reserva->student->last_name)
                    : 'Alumno';
                $vehicleLabel = $reserva->vehicle
                    ? "{$reserva->vehicle->modelo} ({$reserva->vehicle->plate})"
                    : 'Vehículo';

                $colors = ReservaCalendarColors::forStatus($reserva->status);

                return [
                    'id' => $reserva->id,
                    'title' => "Clase — {$studentName}",
                    'start' => $reserva->startsAt(),
                    'end' => $reserva->endsAt(),
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'textColor' => $colors['text'],
                    'classNames' => [$colors['class']],
                    'extendedProps' => [
                        'student' => $studentName,
                        'vehicle' => $vehicleLabel,
                        'status' => $reserva->status,
                        'date' => $reserva->date,
                        'time' => $reserva->time,
                        'endTime' => date('H:i', strtotime($reserva->endsAt())),
                    ],
                ];
            });

        return response()->json($events->values());
    }
}
