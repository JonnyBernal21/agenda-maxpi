<?php

namespace App\Support;

class ReservaCalendarLabels
{
    public static function availableEventTitle(int $cupos, string $time): string
    {
        $time = substr($time, 0, 5);
        $cuposLabel = $cupos === 1 ? '1 cupo' : "{$cupos} cupos";

        return "Disponible · {$cuposLabel} a las {$time}";
    }

    public static function cuposEnHorario(int $freeInstructors, int $freeVehicles): int
    {
        if ($freeInstructors === 0 || $freeVehicles === 0) {
            return 0;
        }

        return min($freeInstructors, $freeVehicles);
    }
}
