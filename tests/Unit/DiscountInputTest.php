<?php

namespace Tests\Unit;

use App\Support\DiscountInput;
use PHPUnit\Framework\TestCase;

class DiscountInputTest extends TestCase
{
    public function test_it_parses_a_percent_with_the_symbol_first(): void
    {
        $parsed = DiscountInput::parse('%15', 4000);

        $this->assertSame(15.0, $parsed['percent']);
        $this->assertSame(600.0, $parsed['amount']);
    }

    public function test_it_parses_a_percent_with_the_symbol_last(): void
    {
        $parsed = DiscountInput::parse('10%', 5200);

        $this->assertSame(10.0, $parsed['percent']);
        $this->assertSame(520.0, $parsed['amount']);
    }

    public function test_it_parses_a_money_amount(): void
    {
        $parsed = DiscountInput::parse('15.00', 4000);

        $this->assertSame(15.0, $parsed['amount']);
        $this->assertSame(0.38, $parsed['percent']);
    }

    public function test_it_formats_whole_percents_with_the_symbol(): void
    {
        $this->assertSame('%15', DiscountInput::display(15, 600));
        $this->assertSame('250.00', DiscountInput::display(6.25, 250));
        $this->assertSame('', DiscountInput::display(0, 0));
    }

    public function test_it_parses_a_surcharge_amount_without_capping_to_the_base(): void
    {
        $parsed = DiscountInput::parseSurcharge('100', 3500);

        $this->assertSame(100.0, $parsed['amount']);
        $this->assertSame(2.86, $parsed['percent']);
    }

    public function test_it_parses_a_surcharge_percent_over_the_base(): void
    {
        $parsed = DiscountInput::parseSurcharge('%10', 3500);

        $this->assertSame(10.0, $parsed['percent']);
        $this->assertSame(350.0, $parsed['amount']);
    }
}
