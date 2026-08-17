<?php

namespace App\Support;

use App\Models\Reservas;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReservaSchedulePayload
{
    /**
     * @param  Collection<int, Reservas>  $reservas
     * @return list<array{
     *     id: int|string,
     *     date: string,
     *     date_label: string,
     *     weekday: string,
     *     time: string,
     *     end_time: string,
     *     instructor: string,
     *     vehicle: string
     * }>
     */
    public static function fromReservas(Collection $reservas): array
    {
        return $reservas
            ->map(function (Reservas $reserva) {
                $date = $reserva->date instanceof \DateTimeInterface
                    ? $reserva->date->format('Y-m-d')
                    : substr((string) $reserva->date, 0, 10);
                $time = Reservas::normalizeTime((string) $reserva->time);
                $parsed = Carbon::parse($date)->locale('es');

                return [
                    'id' => $reserva->id,
                    'date' => $date,
                    'date_label' => $parsed->isoFormat('D [de] MMMM YYYY'),
                    'weekday' => $parsed->isoFormat('dddd'),
                    'time' => $time,
                    'end_time' => date('H:i', strtotime($reserva->endsAt())),
                    'instructor' => $reserva->instructor?->fullName() ?: '—',
                    'vehicle' => $reserva->vehicle
                        ? trim($reserva->vehicle->modelo.' ('.$reserva->vehicle->plate.')')
                        : '—',
                ];
            })
            ->values()
            ->all();
    }
}
