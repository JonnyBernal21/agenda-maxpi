<?php

namespace Tests\Unit;

use App\Support\DayGreeting;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DayGreetingTest extends TestCase
{
    public function test_morning_uses_buenos_dias_and_sun(): void
    {
        $greeting = new DayGreeting(Carbon::parse('2026-10-13 08:15:00'));

        $this->assertSame('Buenos días', $greeting->phrase());
        $this->assertSame('bi-sun-fill', $greeting->icon());
        $this->assertSame('morning', $greeting->period());
        $this->assertSame('Buenos días, Juan Manuel', $greeting->headline('Juan Manuel'));
    }

    public function test_afternoon_uses_buenas_tardes_and_sunset(): void
    {
        $greeting = new DayGreeting(Carbon::parse('2026-10-13 15:00:00'));

        $this->assertSame('Buenas tardes', $greeting->phrase());
        $this->assertSame('bi-sunset-fill', $greeting->icon());
        $this->assertSame('afternoon', $greeting->period());
    }

    public function test_evening_uses_buenas_noches_and_moon(): void
    {
        $greeting = new DayGreeting(Carbon::parse('2026-10-13 20:30:00'));

        $this->assertSame('Buenas noches', $greeting->phrase());
        $this->assertSame('bi-moon-stars-fill', $greeting->icon());
        $this->assertSame('night', $greeting->period());
    }

    public function test_early_morning_is_still_good_night(): void
    {
        $greeting = new DayGreeting(Carbon::parse('2026-10-13 03:10:00'));

        $this->assertSame('Buenas noches', $greeting->phrase());
        $this->assertSame('night', $greeting->period());
    }

    public function test_date_label_keeps_spanish_prepositions_lowercase(): void
    {
        $this->assertSame(
            'Jueves 15 de octubre de 2026',
            DayGreeting::capitalize('jueves 15 de octubre de 2026')
        );
    }
}
