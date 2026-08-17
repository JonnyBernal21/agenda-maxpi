<?php

namespace App\Services;

use App\Models\Reservas;
use Illuminate\Support\Collection;

class ReservaAvailabilityService
{
    public const MAX_CLASSES_PER_DAY = 2;

    public function __construct(
        private readonly SameDayScheduleCutoff $sameDayCutoff,
    ) {}

    /**
     * @return array{
     *     available: bool,
     *     instructor_busy: bool,
     *     vehicle_busy: bool,
     *     student_busy: bool,
     *     student_daily_limit: bool,
     *     messages: list<string>,
     *     instructor_busy_from: ?string,
     *     instructor_busy_until: ?string,
     *     next_time: ?string
     * }
     */
    public function check(
        string $date,
        string $time,
        ?int $instructorId = null,
        ?int $vehicleId = null,
        ?int $studentId = null,
        ?int $excludeReservaId = null,
    ): array {
        $messages = [];
        $instructorBusy = false;
        $vehicleBusy = false;
        $studentBusy = false;
        $studentDailyLimit = false;
        $instructorBusyFrom = null;
        $instructorBusyUntil = null;
        $nextTime = null;

        if ($this->sameDayCutoff->blocksDate($date)) {
            return [
                'available' => false,
                'instructor_busy' => false,
                'vehicle_busy' => false,
                'student_busy' => false,
                'student_daily_limit' => false,
                'messages' => [$this->sameDayCutoff->message()],
                'instructor_busy_from' => null,
                'instructor_busy_until' => null,
                'next_time' => null,
            ];
        }

        $time = Reservas::normalizeTime($time);

        if ($instructorId) {
            $overlap = $this->firstOverlap('instructor_id', (string) $instructorId, $date, $time, $excludeReservaId);
            $instructorBusy = $overlap !== null;

            if ($overlap) {
                $instructorBusyFrom = $overlap['start'];
                $instructorBusyUntil = $overlap['end'];
                $nextTime = $this->nextAvailableTime('instructor_id', (string) $instructorId, $date, $time, $excludeReservaId);
                $nextHint = $nextTime
                    ? " La siguiente hora disponible ese día es {$nextTime}."
                    : ' No hay otro horario libre ese día.';
                $messages[] = "Este instructor ya tiene clase el {$date} de {$overlap['start']} a {$overlap['end']}.{$nextHint}";
            }
        }

        if ($vehicleId) {
            $vehicleBusy = $this->firstOverlap('vehicle_id', (string) $vehicleId, $date, $time, $excludeReservaId) !== null;
            if ($vehicleBusy) {
                $messages[] = 'El vehículo ya está ocupado en esa fecha y horario (las clases duran 2 horas).';
            }
        }

        if ($studentId) {
            $studentBusy = $this->firstOverlap('student_id', (string) $studentId, $date, $time, $excludeReservaId) !== null;
            if ($studentBusy) {
                $messages[] = 'El alumno ya tiene una clase que se cruza con ese horario.';
            }

            $classesOnDay = $this->countStudentClassesOnDate($studentId, $date, $excludeReservaId);

            if ($classesOnDay >= self::MAX_CLASSES_PER_DAY && ! $studentBusy) {
                $studentDailyLimit = true;
                $messages[] = 'El alumno ya tiene '.self::MAX_CLASSES_PER_DAY.' clases ese día. Solo se permiten horarios distintos (máx. '.self::MAX_CLASSES_PER_DAY.' por día).';
            }
        }

        return [
            'available' => ! $instructorBusy && ! $vehicleBusy && ! $studentBusy && ! $studentDailyLimit,
            'instructor_busy' => $instructorBusy,
            'vehicle_busy' => $vehicleBusy,
            'student_busy' => $studentBusy,
            'student_daily_limit' => $studentDailyLimit,
            'messages' => $messages,
            'instructor_busy_from' => $instructorBusyFrom,
            'instructor_busy_until' => $instructorBusyUntil,
            'next_time' => $nextTime,
        ];
    }

    /**
     * @param  list<string>  $dates
     * @return list<array{
     *     date: string,
     *     busy: bool,
     *     busy_from: ?string,
     *     busy_until: ?string,
     *     next_time: ?string,
     *     message: ?string
     * }>
     */
    public function instructorConflicts(int $instructorId, string $time, array $dates): array
    {
        $time = Reservas::normalizeTime($time);
        $rows = [];

        foreach (array_unique($dates) as $date) {
            if ($this->sameDayCutoff->blocksDate($date)) {
                $rows[] = [
                    'date' => $date,
                    'busy' => true,
                    'busy_from' => null,
                    'busy_until' => null,
                    'next_time' => null,
                    'message' => $this->sameDayCutoff->message(),
                ];
                continue;
            }

            $overlap = $this->firstOverlap('instructor_id', (string) $instructorId, $date, $time, null);

            if ($overlap === null) {
                $rows[] = [
                    'date' => $date,
                    'busy' => false,
                    'busy_from' => null,
                    'busy_until' => null,
                    'next_time' => null,
                    'message' => null,
                ];
                continue;
            }

            $nextTime = $this->nextAvailableTime('instructor_id', (string) $instructorId, $date, $time, null);
            $nextHint = $nextTime
                ? " La siguiente clase puede iniciar a las {$nextTime}."
                : ' No hay otro horario libre ese día.';

            $rows[] = [
                'date' => $date,
                'busy' => true,
                'busy_from' => $overlap['start'],
                'busy_until' => $overlap['end'],
                'next_time' => $nextTime,
                'message' => "Este instructor ya tiene clase el {$date} de {$overlap['start']} a {$overlap['end']}.{$nextHint}",
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{date: string, time: string}>  $slots
     * @return list<array{
     *     date: string,
     *     time: string,
     *     busy: bool,
     *     instructor_busy: bool,
     *     vehicle_busy: bool,
     *     next_time: ?string,
     *     message: ?string
     * }>
     */
    public function slotConflicts(int $instructorId, ?int $vehicleId, array $slots): array
    {
        $rows = [];

        foreach ($slots as $slot) {
            $date = (string) ($slot['date'] ?? '');
            $time = Reservas::normalizeTime((string) ($slot['time'] ?? ''));

            if ($this->sameDayCutoff->blocksDate($date)) {
                $rows[] = [
                    'date' => $date,
                    'time' => $time,
                    'busy' => true,
                    'instructor_busy' => false,
                    'vehicle_busy' => false,
                    'next_time' => null,
                    'message' => $this->sameDayCutoff->message(),
                ];
                continue;
            }

            $instructorOverlap = $this->firstOverlap('instructor_id', (string) $instructorId, $date, $time, null);
            $slotVehicleId = isset($slot['vehicle_id']) && $slot['vehicle_id'] !== '' && $slot['vehicle_id'] !== null
                ? (int) $slot['vehicle_id']
                : $vehicleId;
            $vehicleOverlap = $slotVehicleId
                ? $this->firstOverlap('vehicle_id', (string) $slotVehicleId, $date, $time, null)
                : null;

            $instructorBusy = $instructorOverlap !== null;
            $vehicleBusy = $vehicleOverlap !== null;
            $busy = $instructorBusy || $vehicleBusy;
            $messages = [];
            $nextTime = $busy
                ? $this->nextFreeSlot($instructorId, $slotVehicleId, $date, $time, null)
                : null;

            if ($instructorBusy) {
                $nextHint = $nextTime
                    ? " Puedes agendar a las {$nextTime}, 2 horas después."
                    : ' No hay otro horario libre para el instructor ese día.';
                $messages[] = "El instructor ya tiene clase el {$date} de {$instructorOverlap['start']} a {$instructorOverlap['end']}.{$nextHint}";
            }

            if ($vehicleBusy && $slotVehicleId) {
                $nextHint = $nextTime
                    ? " Puedes agendar a las {$nextTime}, 2 horas después."
                    : ' El vehículo no tiene otro horario libre ese día.';
                $messages[] = "El vehículo ya está ocupado el {$date} de {$vehicleOverlap['start']} a {$vehicleOverlap['end']}.{$nextHint}";
            }

            $rows[] = [
                'date' => $date,
                'time' => $time,
                'busy' => $busy,
                'instructor_busy' => $instructorBusy,
                'vehicle_busy' => $vehicleBusy,
                'next_time' => $nextTime,
                'message' => $messages !== [] ? implode(' ', $messages) : null,
            ];
        }

        return $rows;
    }

    public function studentCanBookSlot(int $studentId, string $date, string $time): bool
    {
        if ($this->firstOverlap('student_id', (string) $studentId, $date, $time, null) !== null) {
            return false;
        }

        return $this->countStudentClassesOnDate($studentId, $date, null) < self::MAX_CLASSES_PER_DAY;
    }

    private function countStudentClassesOnDate(int $studentId, string $date, ?int $excludeReservaId): int
    {
        $query = Reservas::query()
            ->active()
            ->where('student_id', (string) $studentId)
            ->where('date', $date);

        if ($excludeReservaId) {
            $query->where('id', '!=', $excludeReservaId);
        }

        return $query->count();
    }

    /**
     * @return array{start: string, end: string}|null
     */
    private function firstOverlap(
        string $column,
        string $value,
        string $date,
        string $time,
        ?int $excludeReservaId,
    ): ?array {
        foreach ($this->existingTimes($column, $value, $date, $excludeReservaId) as $existingTime) {
            if (Reservas::slotsOverlap($time, $existingTime)) {
                return [
                    'start' => $existingTime,
                    'end' => Reservas::slotEndTime($existingTime),
                ];
            }
        }

        return null;
    }

    private function nextFreeSlot(
        int $instructorId,
        ?int $vehicleId,
        string $date,
        string $proposedTime,
        ?int $excludeReservaId,
    ): ?string {
        $proposedMinutes = Reservas::timeToMinutes($proposedTime);
        $latestEnd = null;

        foreach ($this->existingTimes('instructor_id', (string) $instructorId, $date, $excludeReservaId) as $existingTime) {
            if (! Reservas::slotsOverlap($proposedTime, $existingTime)) {
                continue;
            }

            $end = Reservas::timeToMinutes(Reservas::slotEndTime($existingTime));
            $latestEnd = $latestEnd === null ? $end : max($latestEnd, $end);
        }

        if ($vehicleId) {
            foreach ($this->existingTimes('vehicle_id', (string) $vehicleId, $date, $excludeReservaId) as $existingTime) {
                if (! Reservas::slotsOverlap($proposedTime, $existingTime)) {
                    continue;
                }

                $end = Reservas::timeToMinutes(Reservas::slotEndTime($existingTime));
                $latestEnd = $latestEnd === null ? $end : max($latestEnd, $end);
            }
        }

        if ($latestEnd === null) {
            $latestEnd = $proposedMinutes + Reservas::CLASS_DURATION_MINUTES;
        }

        foreach (Reservas::halfHourTimes() as $slot) {
            if (Reservas::timeToMinutes($slot) < $latestEnd) {
                continue;
            }

            if ($this->firstOverlap('instructor_id', (string) $instructorId, $date, $slot, $excludeReservaId) !== null) {
                continue;
            }

            if ($vehicleId && $this->firstOverlap('vehicle_id', (string) $vehicleId, $date, $slot, $excludeReservaId) !== null) {
                continue;
            }

            return $slot;
        }

        return null;
    }

    private function nextAvailableTime(
        string $column,
        string $value,
        string $date,
        string $proposedTime,
        ?int $excludeReservaId,
    ): ?string {
        $existing = $this->existingTimes($column, $value, $date, $excludeReservaId);
        $latestEnd = null;

        foreach ($existing as $existingTime) {
            if (! Reservas::slotsOverlap($proposedTime, $existingTime)) {
                continue;
            }

            $end = Reservas::timeToMinutes(Reservas::slotEndTime($existingTime));
            $latestEnd = $latestEnd === null ? $end : max($latestEnd, $end);
        }

        if ($latestEnd === null) {
            return null;
        }

        foreach (Reservas::halfHourTimes() as $slot) {
            if (Reservas::timeToMinutes($slot) < $latestEnd) {
                continue;
            }

            $overlaps = false;

            foreach ($existing as $existingTime) {
                if (Reservas::slotsOverlap($slot, $existingTime)) {
                    $overlaps = true;
                    break;
                }
            }

            if (! $overlaps) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function existingTimes(string $column, string $value, string $date, ?int $excludeReservaId): array
    {
        $query = Reservas::query()
            ->active()
            ->where($column, $value)
            ->where('date', $date);

        if ($excludeReservaId) {
            $query->where('id', '!=', $excludeReservaId);
        }

        return $query
            ->pluck('time')
            ->map(fn ($time) => Reservas::normalizeTime((string) $time))
            ->values()
            ->all();
    }
}
