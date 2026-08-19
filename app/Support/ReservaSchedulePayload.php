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
     *     time_label: string,
     *     end_time_label: string,
     *     schedule_label: string,
     *     instructor: string,
     *     vehicle: string,
     *     vehicle_type: string
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
                $endTime = date('H:i', strtotime($reserva->endsAt()));
                $parsed = Carbon::parse($date)->locale('es');

                return [
                    'id' => $reserva->id,
                    'date' => $date,
                    'date_label' => $parsed->isoFormat('D [de] MMMM YYYY'),
                    'weekday' => self::capitalize($parsed->isoFormat('dddd')),
                    'time' => $time,
                    'end_time' => $endTime,
                    'time_label' => self::formatHour($time),
                    'end_time_label' => self::formatHour($endTime),
                    'schedule_label' => self::formatHour($time).' – '.self::formatHour($endTime),
                    'instructor' => $reserva->instructor?->fullName() ?: '—',
                    'vehicle' => $reserva->vehicle
                        ? trim($reserva->vehicle->modelo.' ('.$reserva->vehicle->plate.')')
                        : '—',
                    'vehicle_type' => $reserva->vehicle?->typeLabel() ?: '—',
                ];
            })
            ->values()
            ->all();
    }

    public static function formatHour(string $time): string
    {
        $parts = explode(':', $time);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);
        $suffix = $hour >= 12 ? 'PM' : 'AM';
        $hour12 = $hour % 12 ?: 12;

        return $hour12.':'.str_pad((string) $minute, 2, '0', STR_PAD_LEFT).' '.$suffix;
    }

    public static function capitalize(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        return mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }
}
