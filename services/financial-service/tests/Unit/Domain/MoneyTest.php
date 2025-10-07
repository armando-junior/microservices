<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\Exceptions\InvalidMoneyException;
use Src\Domain\ValueObjects\Money;

class MoneyTest extends TestCase
{
    public function test_it_creates_from_float(): void
    {
        $money = Money::fromFloat(150.50);

        $this->assertEquals(15050, $money->cents());
        $this->assertEquals(150.50, $money->toFloat());
        $this->assertEquals('150.50', $money->toString());
    }

    public function test_it_creates_from_cents(): void
    {
        $money = Money::fromCents(15050);

        $this->assertEquals(15050, $money->cents());
        $this->assertEquals(150.50, $money->toFloat());
    }

    public function test_it_creates_zero(): void
    {
        $money = Money::zero();

        $this->assertEquals(0, $money->cents());
        $this->assertTrue($money->isZero());
    }

    public function test_it_throws_exception_for_negative_float(): void
    {
        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        Money::fromFloat(-10.50);
    }

    public function test_it_throws_exception_for_negative_cents(): void
    {
        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        Money::fromCents(-1050);
    }

    public function test_it_adds_two_amounts(): void
    {
        $money1 = Money::fromFloat(100.00);
        $money2 = Money::fromFloat(50.50);

        $result = $money1->add($money2);

        $this->assertEquals(150.50, $result->toFloat());
    }

    public function test_it_subtracts_two_amounts(): void
    {
        $money1 = Money::fromFloat(100.00);
        $money2 = Money::fromFloat(30.00);

        $result = $money1->subtract($money2);

        $this->assertEquals(70.00, $result->toFloat());
    }

    public function test_it_throws_exception_when_subtraction_results_in_negative(): void
    {
        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Subtraction would result in negative amount');

        $money1 = Money::fromFloat(50.00);
        $money2 = Money::fromFloat(100.00);

        $money1->subtract($money2);
    }

    public function test_it_multiplies_by_factor(): void
    {
        $money = Money::fromFloat(50.00);

        $result = $money->multiply(2.5);

        $this->assertEquals(125.00, $result->toFloat());
    }

    public function test_it_calculates_percentage(): void
    {
        $money = Money::fromFloat(100.00);

        $result = $money->percentage(10);

        $this->assertEquals(10.00, $result->toFloat());
    }

    public function test_it_throws_exception_for_invalid_percentage(): void
    {
        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Percentage must be between 0 and 100');

        $money = Money::fromFloat(100.00);
        $money->percentage(150);
    }

    public function test_it_compares_greater_than(): void
    {
        $money1 = Money::fromFloat(100.00);
        $money2 = Money::fromFloat(50.00);

        $this->assertTrue($money1->greaterThan($money2));
        $this->assertFalse($money2->greaterThan($money1));
    }

    public function test_it_compares_less_than(): void
    {
        $money1 = Money::fromFloat(50.00);
        $money2 = Money::fromFloat(100.00);

        $this->assertTrue($money1->lessThan($money2));
        $this->assertFalse($money2->lessThan($money1));
    }

    public function test_it_compares_equality(): void
    {
        $money1 = Money::fromFloat(100.00);
        $money2 = Money::fromFloat(100.00);
        $money3 = Money::fromFloat(50.00);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }

    public function test_it_formats_to_brl(): void
    {
        $money = Money::fromFloat(1234.56);

        $this->assertEquals('R$ 1.234,56', $money->toBRL());
    }
}


