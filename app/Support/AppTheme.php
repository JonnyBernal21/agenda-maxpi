<?php

namespace App\Support;

class AppTheme
{
    public const DEFAULT = 'maxpi';

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     scheme: 'light'|'dark',
     *     swatches: list<string>,
     *     tokens: array<string, string>
     * }>
     */
    public static function all(): array
    {
        return [
            'maxpi' => [
                'label' => 'MaxPi',
                'description' => 'Amarillo de marca y carbón, como el logo.',
                'scheme' => 'light',
                'swatches' => ['#F7E018', '#201E1F', '#FFFFFF'],
                'tokens' => [
                    'accent' => '#f7e018',
                    'accent_hover' => '#e6ce0f',
                    'accent_soft' => '#fff6b8',
                    'accent_border' => '#ead24a',
                    'accent_ink' => '#201e1f',
                    'ink' => '#201e1f',
                    'canvas' => '#f5f5f3',
                    'surface' => '#ffffff',
                    'nav' => '#201e1f',
                    'focus' => 'rgba(247, 224, 24, 0.45)',
                    'primary_rgb' => '32, 30, 31',
                ],
            ],
            'pizarra' => [
                'label' => 'Pizarra',
                'description' => 'Azul corporativo y grises, más formal.',
                'scheme' => 'light',
                'swatches' => ['#2563EB', '#0F172A', '#F8FAFC'],
                'tokens' => [
                    'accent' => '#2563eb',
                    'accent_hover' => '#1d4ed8',
                    'accent_soft' => '#dbeafe',
                    'accent_border' => '#93c5fd',
                    'accent_ink' => '#ffffff',
                    'ink' => '#0f172a',
                    'canvas' => '#f8fafc',
                    'surface' => '#ffffff',
                    'nav' => '#0b1220',
                    'focus' => 'rgba(37, 99, 235, 0.28)',
                    'primary_rgb' => '15, 23, 42',
                ],
            ],
            'bosque' => [
                'label' => 'Bosque',
                'description' => 'Verde y blanco, limpio y de confianza.',
                'scheme' => 'light',
                'swatches' => ['#059669', '#14532D', '#F4FAF6'],
                'tokens' => [
                    'accent' => '#059669',
                    'accent_hover' => '#047857',
                    'accent_soft' => '#d1fae5',
                    'accent_border' => '#6ee7b7',
                    'accent_ink' => '#ffffff',
                    'ink' => '#14532d',
                    'canvas' => '#f4faf6',
                    'surface' => '#ffffff',
                    'nav' => '#14532d',
                    'focus' => 'rgba(5, 150, 105, 0.28)',
                    'primary_rgb' => '20, 83, 45',
                ],
            ],
            'cobre' => [
                'label' => 'Cobre',
                'description' => 'Terracota y arena, cálido como el atardecer.',
                'scheme' => 'light',
                'swatches' => ['#EA580C', '#431407', '#FFF7ED'],
                'tokens' => [
                    'accent' => '#ea580c',
                    'accent_hover' => '#c2410c',
                    'accent_soft' => '#ffedd5',
                    'accent_border' => '#fdba74',
                    'accent_ink' => '#ffffff',
                    'ink' => '#431407',
                    'canvas' => '#fff7ed',
                    'surface' => '#ffffff',
                    'nav' => '#431407',
                    'focus' => 'rgba(234, 88, 12, 0.28)',
                    'primary_rgb' => '67, 20, 7',
                ],
            ],
            'nocturno' => [
                'label' => 'Nocturno',
                'description' => 'Fondo oscuro con acento amarillo.',
                'scheme' => 'dark',
                'swatches' => ['#F7E018', '#201E1F', '#2A2829'],
                'tokens' => [
                    'accent' => '#f7e018',
                    'accent_hover' => '#e6ce0f',
                    'accent_soft' => '#2c2a28',
                    'accent_border' => '#4a4746',
                    'accent_ink' => '#201e1f',
                    'ink' => '#f5f5f3',
                    'canvas' => '#161414',
                    'surface' => '#242222',
                    'nav' => '#100f0f',
                    'focus' => 'rgba(247, 224, 24, 0.4)',
                    'primary_rgb' => '245, 245, 243',
                ],
            ],
            'abismo' => [
                'label' => 'Abismo',
                'description' => 'Oscuro con azul, para trabajar de noche.',
                'scheme' => 'dark',
                'swatches' => ['#3B82F6', '#0B1220', '#1E293B'],
                'tokens' => [
                    'accent' => '#3b82f6',
                    'accent_hover' => '#2563eb',
                    'accent_soft' => '#1e3a5f',
                    'accent_border' => '#60a5fa',
                    'accent_ink' => '#ffffff',
                    'ink' => '#f5f5f3',
                    'canvas' => '#161414',
                    'surface' => '#242222',
                    'nav' => '#0b1220',
                    'focus' => 'rgba(59, 130, 246, 0.4)',
                    'primary_rgb' => '245, 245, 243',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isValid(?string $key): bool
    {
        return is_string($key) && isset(self::all()[$key]);
    }

    /**
     * @return array<string, string>
     */
    public static function tokens(?string $key): array
    {
        $themes = self::all();

        return $themes[self::isValid($key) ? $key : self::DEFAULT]['tokens'];
    }

    public static function scheme(?string $key): string
    {
        $themes = self::all();
        $valid = self::isValid($key) ? $key : self::DEFAULT;

        return $themes[$valid]['scheme'];
    }

    public static function isDark(?string $key): bool
    {
        return self::scheme($key) === 'dark';
    }
}
