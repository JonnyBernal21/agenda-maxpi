<?php

namespace Tests\Unit;

use App\Models\Reservas;
use PHPUnit\Framework\TestCase;

class ReservaHoursTest extends TestCase
{
    public function test_half_hour_times_start_at_seven(): void
    {
        $times = Reservas::halfHourTimes();

        $this->assertSame('07:00', $times[0]);
        $this->assertSame('07:30', $times[1]);
        $this->assertSame('08:00', $times[2]);
        $this->assertContains('19:00', $times);
        $this->assertSame('19:00', end($times));
    }

    public function test_available_times_are_two_hour_blocks_from_seven(): void
    {
        $this->assertSame(
            ['07:00', '09:00', '11:00', '13:00', '15:00', '17:00'],
            Reservas::availableTimes()
        );
    }
}
