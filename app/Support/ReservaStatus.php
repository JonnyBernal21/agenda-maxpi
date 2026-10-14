<?php

namespace App\Support;

class ReservaStatus
{
    /**
     * @var array<string, string>
     */
    private const LABELS = [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'en_curso' => 'En curso',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
    ];

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function badgeClass(string $status): string
    {
        return match ($status) {
            'confirmada' => 'badge--status-confirmada',
            'en_curso' => 'badge--status-en-curso',
            'completada' => 'badge--status-completada',
            'cancelada' => 'badge--status-cancelada',
            default => 'badge--status-pendiente',
        };
    }
}
