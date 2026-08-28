<?php

namespace App\Support;

use App\Models\Reservas;
use Illuminate\Support\Collection;

class ReservaClassNumbers
{
    /**
     * Sequential class number per student, in date/time order, skipping cancelled classes.
     *
     * @param  Collection<int, Reservas>  $reservas
     * @return array<int, int>
     */
    public static function mapFor(Collection $reservas): array
    {
        $studentIds = $reservas
            ->pluck('student_id')
            ->unique()
            ->filter()
            ->values();

        if ($studentIds->isEmpty()) {
            return [];
        }

        $ordered = Reservas::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', '!=', 'cancelada')
            ->orderBy('date')
            ->orderBy('time')
            ->orderBy('id')
            ->get(['id', 'student_id']);

        $numbers = [];
        $counts = [];

        foreach ($ordered as $row) {
            $studentId = (string) $row->student_id;
            $counts[$studentId] = ($counts[$studentId] ?? 0) + 1;
            $numbers[(int) $row->id] = $counts[$studentId];
        }

        return $numbers;
    }
}
