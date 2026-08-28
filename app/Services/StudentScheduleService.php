<?php

namespace App\Services;

use App\Models\Reservas;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentScheduleService
{
    public const WEEKDAY_LABELS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function __construct(
        private readonly ReservaAvailabilityService $availability,
        private readonly SameDayScheduleCutoff $sameDayCutoff,
    ) {}

    /**
     * @param  list<int>  $weekdays
     * @return list<string>
     */
    public function datesFor(string $startDate, array $weekdays, int $count): array
    {
        $weekdays = array_values(array_unique(array_map('intval', $weekdays)));
        $cursor = Carbon::parse($startDate)->startOfDay();
        $limit = $cursor->copy()->addYear();
        $dates = [];

        while (count($dates) < $count && $cursor->lte($limit)) {
            if (in_array($cursor->isoWeekday(), $weekdays, true)) {
                $dates[] = $cursor->toDateString();
            }

            $cursor->addDay();
        }

        return $dates;
    }

    /**
     * @param  list<int>  $weekdays
     * @param  array<string, string>  $timesByDate
     * @param  array<string, int>  $vehiclesByDate
     * @return Collection<int, Reservas>
     */
    public function assign(
        Student $student,
        string $startDate,
        array $weekdays,
        string $time,
        int $instructorId,
        int $vehicleId,
        array $timesByDate = [],
        array $vehiclesByDate = [],
    ): Collection {
        $student->load(['course', 'extraClasses']);

        if (! $student->course) {
            throw ValidationException::withMessages([
                'student_id' => 'El alumno no tiene un curso asignado.',
            ]);
        }

        $needed = $student->remainingClasses();

        if ($needed < 1) {
            throw ValidationException::withMessages([
                'student_id' => 'El alumno ya no tiene clases disponibles en su curso.',
            ]);
        }

        if ($this->sameDayCutoff->blocksDate($startDate)) {
            throw ValidationException::withMessages([
                'start_date' => $this->sameDayCutoff->message(),
            ]);
        }

        $dates = $this->datesFor($startDate, $weekdays, $needed);

        if (count($dates) < $needed) {
            throw ValidationException::withMessages([
                'weekdays' => 'No se pudieron generar suficientes fechas con los días seleccionados.',
            ]);
        }

        $conflicts = [];

        foreach ($dates as $date) {
            $slotTime = $timesByDate[$date] ?? $time;
            $slotVehicle = (int) ($vehiclesByDate[$date] ?? $vehicleId);
            $check = $this->availability->check(
                $date,
                $slotTime,
                $instructorId,
                $slotVehicle,
                (int) $student->id,
            );

            if (! $check['available']) {
                $label = Carbon::parse($date)->locale('es')->isoFormat('ddd D MMM');
                $conflicts[] = $label.' '.$slotTime.': '.implode(' ', $check['messages']);
            }
        }

        if ($conflicts !== []) {
            throw ValidationException::withMessages([
                'time' => 'Hay conflictos en el horario. '.implode(' ', array_slice($conflicts, 0, 4)),
            ]);
        }

        $created = DB::transaction(function () use ($dates, $student, $instructorId, $vehicleId, $time, $timesByDate, $vehiclesByDate) {
            $rows = [];

            foreach ($dates as $date) {
                $rows[] = Reservas::query()->create([
                    'student_id' => (string) $student->id,
                    'instructor_id' => (string) $instructorId,
                    'vehicle_id' => (string) ($vehiclesByDate[$date] ?? $vehicleId),
                    'date' => $date,
                    'time' => $timesByDate[$date] ?? $time,
                    'status' => 'pendiente',
                ]);
            }

            return $rows;
        });

        return Reservas::query()
            ->with(['instructor', 'vehicle'])
            ->whereIn('id', collect($created)->pluck('id'))
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }
}
