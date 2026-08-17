<?php

namespace App\Services;

use Carbon\Carbon;

class SameDayScheduleCutoff
{
    public const TIMEZONE = 'America/Mexico_City';

    public const CUTOFF_TIME = '09:00';

    public function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public function isSameDayBlocked(): bool
    {
        return $this->now()->format('H:i') >= self::CUTOFF_TIME;
    }

    public function minBookableDate(): string
    {
        $today = $this->now()->toDateString();

        if ($this->isSameDayBlocked()) {
            return $this->now()->addDay()->toDateString();
        }

        return $today;
    }

    public function blocksDate(?string $date): bool
    {
        if ($date === null || $date === '') {
            return false;
        }

        return $date === $this->now()->toDateString() && $this->isSameDayBlocked();
    }

    public function message(): string
    {
        return 'A partir de las 9:00 AM no se pueden agendar clases ni horarios para el día de hoy. Elige una fecha a partir de mañana.';
    }
}
