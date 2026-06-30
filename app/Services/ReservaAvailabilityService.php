<?php

namespace App\Services;

use App\Models\Reservas;

class ReservaAvailabilityService
{
    public const MAX_CLASSES_PER_DAY = 2;

    /**
     * @return array{
     *     available: bool,
     *     instructor_busy: bool,
     *     vehicle_busy: bool,
     *     student_busy: bool,
     *     student_daily_limit: bool,
     *     messages: list<string>
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

        if ($instructorId) {
            $instructorBusy = $this->hasConflict('instructor_id', (string) $instructorId, $date, $time, $excludeReservaId);
            if ($instructorBusy) {
                $messages[] = 'El instructor ya tiene una clase reservada en esa fecha y hora.';
            }
        }

        if ($vehicleId) {
            $vehicleBusy = $this->hasConflict('vehicle_id', (string) $vehicleId, $date, $time, $excludeReservaId);
            if ($vehicleBusy) {
                $messages[] = 'El vehículo ya está ocupado en esa fecha y hora.';
            }
        }

        if ($studentId) {
            $studentBusy = $this->hasConflict('student_id', (string) $studentId, $date, $time, $excludeReservaId);
            if ($studentBusy) {
                $messages[] = 'El alumno ya tiene una clase en ese horario.';
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
        ];
    }

    public function studentCanBookSlot(int $studentId, string $date, string $time): bool
    {
        if ($this->hasConflict('student_id', (string) $studentId, $date, $time, null)) {
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

    private function hasConflict(
        string $column,
        string $value,
        string $date,
        string $time,
        ?int $excludeReservaId,
    ): bool {
        $query = Reservas::query()
            ->active()
            ->where($column, $value)
            ->where('date', $date)
            ->where('time', $time);

        if ($excludeReservaId) {
            $query->where('id', '!=', $excludeReservaId);
        }

        return $query->exists();
    }
}
