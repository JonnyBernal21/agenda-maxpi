<?php

namespace App\Support;

final class ClassEvaluationCatalog
{
    /**
     * @return list<array{id: string, title: string, items: list<array{id: string, title: string, hint: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'id' => 'prep',
                'title' => 'Preparación del vehículo',
                'items' => [
                    [
                        'id' => 'seat_mirrors_belt',
                        'title' => 'Ajuste del asiento, espejos y cinturón',
                        'hint' => 'Prepara el vehículo de forma segura.',
                    ],
                    [
                        'id' => 'start_engine',
                        'title' => 'Encendido y puesta en marcha',
                        'hint' => 'Arranca y pone el vehículo en movimiento con control.',
                    ],
                ],
            ],
            [
                'id' => 'control',
                'title' => 'Control del vehículo',
                'items' => [
                    [
                        'id' => 'transmission',
                        'title' => 'Embrague, cambios o manejo automático',
                        'hint' => 'Usa la transmisión de forma suave y oportuna.',
                    ],
                    [
                        'id' => 'steering',
                        'title' => 'Control del volante',
                        'hint' => 'Mantiene las manos y la trayectoria estables.',
                    ],
                    [
                        'id' => 'speed',
                        'title' => 'Control de velocidad',
                        'hint' => 'Adapta la velocidad al entorno y al tráfico.',
                    ],
                    [
                        'id' => 'braking',
                        'title' => 'Frenado',
                        'hint' => 'Frena con anticipación y suavidad.',
                    ],
                ],
            ],
            [
                'id' => 'awareness',
                'title' => 'Observación y seguridad',
                'items' => [
                    [
                        'id' => 'signals',
                        'title' => 'Uso de direccionales y espejos',
                        'hint' => 'Comunica maniobras a tiempo.',
                    ],
                    [
                        'id' => 'distance',
                        'title' => 'Distancia de seguimiento',
                        'hint' => 'Conserva un espacio de seguridad.',
                    ],
                    [
                        'id' => 'signs',
                        'title' => 'Respeto a señalamientos',
                        'hint' => 'Obedece semáforos, altos y límites.',
                    ],
                    [
                        'id' => 'blind_spots',
                        'title' => 'Puntos ciegos e incorporación',
                        'hint' => 'Revisa e incorpora con seguridad.',
                    ],
                ],
            ],
            [
                'id' => 'maneuvers',
                'title' => 'Maniobras',
                'items' => [
                    [
                        'id' => 'parking',
                        'title' => 'Estacionamiento',
                        'hint' => 'Estaciona con control y referencias claras.',
                    ],
                    [
                        'id' => 'reverse',
                        'title' => 'Reversa',
                        'hint' => 'Retrocede con visibilidad y precisión.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array{id: string, title: string, hint: string}>
     */
    public static function items(): array
    {
        return collect(self::groups())->flatMap(fn (array $group) => $group['items'])->values()->all();
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_column(self::items(), 'id');
    }

    /**
     * @return array<int, string>
     */
    public static function scale(): array
    {
        return [
            0 => 'No realizado o inseguro',
            1 => 'Deficiente',
            2 => 'Necesita mucha ayuda',
            3 => 'En proceso, con algunos errores',
            4 => 'Bueno, con errores menores',
            5 => 'Correcto, seguro e independiente',
        ];
    }

    public static function scoreLabel(int $score): string
    {
        return self::scale()[$score] ?? (string) $score;
    }
};
