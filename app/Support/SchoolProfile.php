<?php

namespace App\Support;

final class SchoolProfile
{
    /**
     * @var array<string, string>
     */
    public const TIMEZONES = [
        'America/Mexico_City' => 'Ciudad de México (Centro)',
        'America/Cancun' => 'Cancún (Sureste)',
        'America/Merida' => 'Mérida',
        'America/Monterrey' => 'Monterrey',
        'America/Matamoros' => 'Matamoros',
        'America/Chihuahua' => 'Chihuahua',
        'America/Mazatlan' => 'Mazatlán (Pacífico)',
        'America/Hermosillo' => 'Hermosillo (Sonora)',
        'America/Tijuana' => 'Tijuana (Noroeste)',
        'America/Bahia_Banderas' => 'Bahía de Banderas',
        'UTC' => 'UTC',
    ];

    /**
     * @var array<string, string>
     */
    public const CURRENCIES = [
        'MXN' => 'MXN — Peso mexicano',
        'USD' => 'USD — Dólar estadounidense',
    ];

    public const DEFAULT_TIMEZONE = 'America/Mexico_City';

    public const DEFAULT_CURRENCY = 'MXN';

    public const DEFAULT_COUNTRY = 'México';
}
