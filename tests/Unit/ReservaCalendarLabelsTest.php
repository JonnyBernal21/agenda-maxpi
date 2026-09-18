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

    public function test_home_class_title_marks_modality(): void
    {
        $this->assertSame(
            'Clase- 1 A domicilio ROBERTO CARLOS VALDEZ GONZALEZ',
            ReservaCalendarLabels::bookedEventTitle('ROBERTO CARLOS VALDEZ GONZALEZ', 1, false, true)
        );
    }

    public function test_school_class_title_omits_modality(): void
    {
        $this->assertSame(
            'Clase- 1 ROBERTO CARLOS VALDEZ GONZALEZ',
            ReservaCalendarLabels::bookedEventTitle('ROBERTO CARLOS VALDEZ GONZALEZ', 1, false, false)
        );
    }

    public function test_cancelled_title_keeps_student_name_without_number(): void
    {
        $this->assertSame(
            'Cancelada — ROBERTO CARLOS VALDEZ GONZALEZ',
            ReservaCalendarLabels::bookedEventTitle('ROBERTO CARLOS VALDEZ GONZALEZ', null, true)
        );
    }

    public function test_cancelled_home_class_title_marks_modality(): void
    {
        $this->assertSame(
            'Cancelada — A domicilio ROBERTO CARLOS VALDEZ GONZALEZ',
            ReservaCalendarLabels::bookedEventTitle('ROBERTO CARLOS VALDEZ GONZALEZ', null, true, true)
        );
    }
}
