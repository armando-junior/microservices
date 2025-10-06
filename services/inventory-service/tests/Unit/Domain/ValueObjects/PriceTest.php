<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\Price;
use Src\Domain\Exceptions\InvalidPriceException;

class PriceTest extends TestCase
{
    public function test_it_creates_valid_price(): void
    {
        $price = Price::fromFloat(99.99);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(99.99, $price->value());
    }

    public function test_it_creates_small_price(): void
    {
        $price = Price::fromFloat(0.01);

        $this->assertEquals(0.01, $price->value());
    }

    public function test_it_creates_zero_price(): void
    {
        $price = Price::fromFloat(0.0);

        $this->assertEquals(0.0, $price->value());
        $this->assertTrue($price->isZero());
    }

    public function test_it_formats_price_correctly(): void
    {
        $price = Price::fromFloat(1234.56);

        $this->assertEquals('1234.56', $price->formatted());
    }

    public function test_it_throws_exception_for_negative_price(): void
    {
        $this->expectException(InvalidPriceException::class);
        $this->expectExceptionMessage('Price cannot be negative');
        
        Price::fromFloat(-10.0);
    }

    public function test_two_prices_with_same_value_are_equal(): void
    {
        $price1 = Price::fromFloat(99.99);
        $price2 = Price::fromFloat(99.99);

        $this->assertTrue($price1->equals($price2));
    }

    public function test_it_compares_greater_than(): void
    {
        $price1 = Price::fromFloat(100.0);
        $price2 = Price::fromFloat(50.0);

        $this->assertTrue($price1->greaterThan($price2));
        $this->assertFalse($price2->greaterThan($price1));
    }

    public function test_it_compares_less_than(): void
    {
        $price1 = Price::fromFloat(50.0);
        $price2 = Price::fromFloat(100.0);

        $this->assertTrue($price1->lessThan($price2));
        $this->assertFalse($price2->lessThan($price1));
    }
}
