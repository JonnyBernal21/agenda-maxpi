<?php

namespace App\Services;

use App\Models\Reservas;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyReportService
{
    /**
     * @return array{
     *     date: string,
     *     date_label: string,
     *     is_today: bool,
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
    public function forDate(CarbonInterface $date): array
    {
        $dateString = $date->toDateString();

        $byStatus = Reservas::query()
            ->where('date', $dateString)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $reservas = Reservas::query()
            ->with(['student', 'instructor', 'vehicle'])
            ->where('date', $dateString)
            ->orderBy('time')
            ->get();

        return [
            'date' => $dateString,
            'date_label' => $date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
            'is_today' => $date->isToday(),
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
}
