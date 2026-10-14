<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class DayGreeting
{
    public function __construct(private CarbonInterface $now)
    {
    }

    public static function make(?CarbonInterface $now = null): self
    {
        return new self($now ?? Carbon::now());
    }

    public function phrase(): string
    {
        return match ($this->period()) {
            'afternoon' => 'Buenas tardes',
            'night' => 'Buenas noches',
            default => 'Buenos días',
        };
    }

    public function icon(): string
    {
        return match ($this->period()) {
            'afternoon' => 'bi-sunset-fill',
            'night' => 'bi-moon-stars-fill',
            default => 'bi-sun-fill',
        };
    }

    public function period(): string
    {
        $hour = (int) $this->now->format('G');

        if ($hour >= 19 || $hour < 5) {
            return 'night';
        }

        if ($hour >= 12) {
            return 'afternoon';
        }

        return 'morning';
    }

    public function headline(string $name = ''): string
    {
        $name = trim($name);

        return $name === '' ? $this->phrase() : $this->phrase().', '.$name;
    }
}
