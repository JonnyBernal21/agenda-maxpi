<?php

namespace App\Services;

use App\Models\Reservas;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyReportService
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     date_label: string,
     *     is_today: bool,
     *     is_single_day: bool,
     *     counts: array{
     *         total: int,
     *         completadas: int,
     *         canceladas: int,
     *         pendientes: int,
     *         confirmadas: int
     *     },
     *     reservas: Collection<int, Reservas>
     * }
     */
    public function forRange(CarbonInterface $from, CarbonInterface $to): array
    {
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->startOfDay()];
        }

        $fromString = $from->toDateString();
        $toString = $to->toDateString();
        $isSingleDay = $fromString === $toString;

        $byStatus = Reservas::query()
            ->whereBetween('date', [$fromString, $toString])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $reservas = Reservas::query()
            ->with(['student', 'instructor', 'vehicle'])
            ->whereBetween('date', [$fromString, $toString])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return [
            'from' => $fromString,
            'to' => $toString,
            'date_label' => $this->periodLabel($from, $to, $isSingleDay),
            'is_today' => $isSingleDay && $from->isToday(),
            'is_single_day' => $isSingleDay,
            'counts' => [
                'total' => (int) $byStatus->sum(),
                'completadas' => (int) ($byStatus['completada'] ?? 0),
                'canceladas' => (int) ($byStatus['cancelada'] ?? 0),
                'pendientes' => (int) ($byStatus['pendiente'] ?? 0),
                'confirmadas' => (int) ($byStatus['confirmada'] ?? 0),
            ],
            'reservas' => $reservas,
        ];
    }

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     date_label: string,
     *     is_today: bool,
     *     is_single_day: bool,
     *     counts: array{total: int, completadas: int, canceladas: int, pendientes: int, confirmadas: int},
     *     reservas: Collection<int, Reservas>
     * }
     */
    public function forDate(CarbonInterface $date): array
    {
        $day = $date->copy()->startOfDay();

        return $this->forRange($day, $day);
    }

    private function periodLabel(CarbonInterface $from, CarbonInterface $to, bool $isSingleDay): string
    {
        $start = $from->copy()->locale('es');
        $end = $to->copy()->locale('es');

        if ($isSingleDay) {
            return $start->isoFormat('dddd D [de] MMMM [de] YYYY');
        }

        if ($start->isSameMonth($end) && $start->isSameYear($end)) {
            return $start->isoFormat('D').' – '.$end->isoFormat('D [de] MMMM [de] YYYY');
        }

        if ($start->isSameYear($end)) {
            return $start->isoFormat('D [de] MMMM').' – '.$end->isoFormat('D [de] MMMM [de] YYYY');
        }

        return $start->isoFormat('D [de] MMMM [de] YYYY').' – '.$end->isoFormat('D [de] MMMM [de] YYYY');
    }
}
