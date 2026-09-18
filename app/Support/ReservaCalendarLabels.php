<?php

namespace App\Support;

class ReservaCalendarLabels
{
    public static function bookedEventTitle(
        string $studentName,
        ?int $classNumber = null,
        bool $cancelled = false,
        bool $isHomeClass = false,
    ): string {
        $label = $cancelled ? 'Cancelada' : 'Clase';
        $modality = $isHomeClass ? ' A domicilio' : '';

        if ($classNumber) {
            return "{$label}- {$classNumber}{$modality} {$studentName}";
        }

        return "{$label} —{$modality} {$studentName}";
    }

    public static function availableEventTitle(int $cupos, string $time): string
    {
        $time = substr($time, 0, 5);
        $cuposLabel = $cupos === 1 ? '1 cupo' : "{$cupos} cupos";

        return "Disponible · {$cuposLabel}";
    }

    public static function cuposEnHorario(int $freeInstructors, int $freeVehicles): int
    {
        if ($freeInstructors === 0 || $freeVehicles === 0) {
            return 0;
        }

        return min($freeInstructors, $freeVehicles);
    }
}
