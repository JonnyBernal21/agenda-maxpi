<?php

namespace App\Support;

class ReservaCalendarColors
{
    /**
     * @return array{background: string, border: string, text: string, class: string}
     */
    public static function forStatus(string $status): array
    {
        return match ($status) {
            'confirmada' => [
                'background' => '#2563eb',
                'border' => '#1d4ed8',
                'text' => '#ffffff',
                'class' => 'fc-event-confirmada',
            ],
            'completada' => [
                'background' => '#16a34a',
                'border' => '#15803d',
                'text' => '#ffffff',
                'class' => 'fc-event-completada',
            ],
            'cancelada' => [
                'background' => '#dc2626',
                'border' => '#b91c1c',
                'text' => '#ffffff',
                'class' => 'fc-event-cancelada',
            ],
            default => [
                'background' => '#f59e0b',
                'border' => '#d97706',
                'text' => '#1e293b',
                'class' => 'fc-event-pendiente',
            ],
        };
    }

    /**
     * Horario reservado por otro alumno (sin revelar datos).
     *
     * @return array{background: string, border: string, text: string, class: string}
     */
    public static function forOccupied(): array
    {
        return [
            'background' => '#e2e8f0',
            'border' => '#94a3b8',
            'text' => '#475569',
            'class' => 'fc-event-occupied',
        ];
    }

    /**
     * Horario libre para reservar.
     *
     * @return array{background: string, border: string, text: string, class: string}
     */
    public static function forAvailable(): array
    {
        return [
            'background' => '#dcfce7',
            'border' => '#22c55e',
            'text' => '#166534',
            'class' => 'fc-event-available',
        ];
    }
}
