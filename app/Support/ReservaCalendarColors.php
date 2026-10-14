<?php

namespace App\Support;

use App\Services\SettingService;

class ReservaCalendarColors
{
    /**
     * @return array{background: string, border: string, text: string, class: string}
     */
    public static function forStatus(string $status): array
    {
        $theme = AppTheme::tokens(
            app(SettingService::class)->current()->theme()
        );

        return match ($status) {
            'confirmada' => [
                'background' => $theme['accent'],
                'border' => $theme['accent_hover'],
                'text' => $theme['accent_ink'],
                'class' => 'fc-event-confirmada',
            ],
            'en_curso' => [
                'background' => $theme['ink'],
                'border' => $theme['ink'],
                'text' => '#ffffff',
                'class' => 'fc-event-en-curso',
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
        if (AppTheme::isDark(self::themeKey())) {
            return [
                'background' => '#2a2829',
                'border' => '#4a4746',
                'text' => '#d4d2d0',
                'class' => 'fc-event-occupied',
            ];
        }

        return [
            'background' => '#ECECEA',
            'border' => '#C8C6C4',
            'text' => '#4A4746',
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
        if (AppTheme::isDark(self::themeKey())) {
            return [
                'background' => '#16351f',
                'border' => '#4ade80',
                'text' => '#bbf7d0',
                'class' => 'fc-event-available',
            ];
        }

        return [
            'background' => '#dcfce7',
            'border' => '#22c55e',
            'text' => '#166534',
            'class' => 'fc-event-available',
        ];
    }

    private static function themeKey(): string
    {
        return app(SettingService::class)->current()->theme();
    }
}
