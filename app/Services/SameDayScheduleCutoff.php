<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class SameDayScheduleCutoff
{
    public const TIMEZONE = 'America/Mexico_City';

    public const CUTOFF_TIME = '09:00';

    public const PAST_DATES_PERMISSION = 'reservas.schedule_past';

    public function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public function allowsPastDates(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user instanceof User && $user->can(self::PAST_DATES_PERMISSION);
    }

    public function isSameDayBlocked(): bool
    {
        if ($this->allowsPastDates()) {
            return false;
        }

        return $this->now()->format('H:i') >= self::CUTOFF_TIME;
    }

    public function minBookableDate(): string
    {
        if ($this->allowsPastDates()) {
            return '';
        }

        $today = $this->now()->toDateString();

        if ($this->isSameDayBlocked()) {
            return $this->now()->addDay()->toDateString();
        }

        return $today;
    }

    /**
     * @return list<string>
     */
    public function additionalDateRules(): array
    {
        $min = $this->minBookableDate();

        return $min !== '' ? ['after_or_equal:'.$min] : [];
    }

    public function blocksDate(?string $date): bool
    {
        if ($date === null || $date === '' || $this->allowsPastDates()) {
            return false;
        }

        return $date === $this->now()->toDateString() && $this->isSameDayBlocked();
    }

    public function message(): string
    {
        return 'A partir de las 9:00 AM no se pueden agendar clases ni horarios para el día de hoy. Elige una fecha a partir de mañana.';
    }
}
