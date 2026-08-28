<?php

namespace Tests\Unit;

use App\Support\ReservaCalendarLabels;
use PHPUnit\Framework\TestCase;

class ReservaCalendarLabelsTest extends TestCase
{
    public function test_booked_title_concatenates_class_number(): void
    {
        $this->assertSame(
            'Clase- 1 ROBERTO CARLOS VALDEZ GONZALEZ',
            ReservaCalendarLabels::bookedEventTitle('ROBERTO CARLOS VALDEZ GONZALEZ', 1)
        );
    }

    public function test_cancelled_title_keeps_student_name_without_number(): void
    {
        $this->assertSame(
            'Cancelada — ROBERTO CARLOS VALDEZ GONZALEZ',
            ReservaCalendarLabels::bookedEventTitle('ROBERTO CARLOS VALDEZ GONZALEZ', null, true)
        );
    }
}
