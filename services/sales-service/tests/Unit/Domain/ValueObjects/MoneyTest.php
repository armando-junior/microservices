<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\Money;
use Src\Domain\Exceptions\InvalidMoneyException;

class MoneyTest extends TestCase
{
    /** @test */
    public function it_creates_valid_money(): void
    {
        $money = Money::fromFloat(100.50);
        
        $this->assertInstanceOf(Money::class, $money);
        $this->assertEquals(100.50, $money->value());
    }

    /** @test */
    public function it_creates_zero_money(): void
    {
        $money = Money::zero();
        
        $this->assertEquals(0.0, $money->value());
    }

    /** @test */
    public function it_throws_exception_for_negative_value(): void
    {
        $this->expectException(InvalidMoneyException::class);
        
        Money::fromFloat(-10.0);
    }

    /** @test */
    public function it_adds_money(): void
    {
        $money1 = Money::fromFloat(100.0);
        $money2 = Money::fromFloat(50.0);
        
        $result = $money1->add($money2);
        
        $this->assertEquals(150.0, $result->value());
    }

    /** @test */
    public function it_subtracts_money(): void
    {
        $money1 = Money::fromFloat(100.0);
        $money2 = Money::fromFloat(30.0);
        
        $result = $money1->subtract($money2);
        
        $this->assertEquals(70.0, $result->value());
    }

    /** @test */
    public function it_multiplies_money(): void
    {
        $money = Money::fromFloat(10.0);
        
        $result = $money->multiply(3.5);
        
        $this->assertEquals(35.0, $result->value());
    }

    /** @test */
    public function it_compares_equality(): void
    {
        $money1 = Money::fromFloat(100.0);
        $money2 = Money::fromFloat(100.0);
        $money3 = Money::fromFloat(200.0);
        
        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }

    /** @test */
    public function it_checks_if_greater_than(): void
    {
        $money1 = Money::fromFloat(100.0);
        $money2 = Money::fromFloat(50.0);
        
        $this->assertTrue($money1->greaterThan($money2));
        $this->assertFalse($money2->greaterThan($money1));
    }

    /** @test */
    public function it_formats_correctly(): void
    {
        $money = Money::fromFloat(1234.56);
        
        // Brazilian format: R$ 1.234,56
        $this->assertStringContainsString('1.234,56', $money->formatted());
    }
}
