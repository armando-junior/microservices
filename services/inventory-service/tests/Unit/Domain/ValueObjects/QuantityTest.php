<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\Quantity;
use Src\Domain\Exceptions\InvalidQuantityException;

class QuantityTest extends TestCase
{
    public function test_it_creates_valid_quantity(): void
    {
        $quantity = Quantity::fromInt(10);

        $this->assertInstanceOf(Quantity::class, $quantity);
        $this->assertEquals(10, $quantity->value());
    }

    public function test_it_creates_zero_quantity(): void
    {
        $quantity = Quantity::fromInt(0);

        $this->assertEquals(0, $quantity->value());
        $this->assertTrue($quantity->isZero());
    }

    public function test_it_throws_exception_for_negative_quantity(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Quantity cannot be negative');
        
        Quantity::fromInt(-5);
    }

    public function test_it_checks_if_sufficient(): void
    {
        $quantity = Quantity::fromInt(10);
        $required = Quantity::fromInt(5);

        $this->assertTrue($quantity->isSufficient($required));
    }

    public function test_it_checks_if_insufficient(): void
    {
        $quantity = Quantity::fromInt(5);
        $required = Quantity::fromInt(10);

        $this->assertFalse($quantity->isSufficient($required));
    }

    public function test_it_compares_greater_than(): void
    {
        $qty1 = Quantity::fromInt(10);
        $qty2 = Quantity::fromInt(5);

        $this->assertTrue($qty1->greaterThan($qty2));
        $this->assertFalse($qty2->greaterThan($qty1));
    }

    public function test_it_adds_quantities(): void
    {
        $qty1 = Quantity::fromInt(10);
        $qty2 = Quantity::fromInt(5);

        $result = $qty1->add($qty2);

        $this->assertEquals(15, $result->value());
    }

    public function test_it_subtracts_quantities(): void
    {
        $qty1 = Quantity::fromInt(10);
        $qty2 = Quantity::fromInt(3);

        $result = $qty1->subtract($qty2);

        $this->assertEquals(7, $result->value());
    }

    public function test_two_quantities_are_equal(): void
    {
        $qty1 = Quantity::fromInt(10);
        $qty2 = Quantity::fromInt(10);

        $this->assertTrue($qty1->equals($qty2));
    }
}
