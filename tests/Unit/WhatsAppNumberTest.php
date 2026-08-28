<?php

namespace Tests\Unit;

use App\Support\WhatsAppNumber;
use PHPUnit\Framework\TestCase;

class WhatsAppNumberTest extends TestCase
{
    public function test_it_adds_mexico_code_to_ten_digit_numbers(): void
    {
        $this->assertSame('525511112222', WhatsAppNumber::digits('55 1111 2222'));
    }

    public function test_it_keeps_an_international_mexico_number(): void
    {
        $this->assertSame('525511112222', WhatsAppNumber::digits('+52 55 1111 2222'));
    }

    public function test_it_strips_the_legacy_mobile_one_after_country_code(): void
    {
        $this->assertSame('525511112222', WhatsAppNumber::digits('+52 1 55 1111 2222'));
    }

    public function test_it_returns_null_when_the_phone_is_empty(): void
    {
        $this->assertNull(WhatsAppNumber::digits(''));
        $this->assertNull(WhatsAppNumber::digits(null));
        $this->assertNull(WhatsAppNumber::digits('123'));
    }
}
