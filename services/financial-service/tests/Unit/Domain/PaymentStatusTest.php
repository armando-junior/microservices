<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\Exceptions\InvalidPaymentStatusException;
use Src\Domain\ValueObjects\PaymentStatus;

class PaymentStatusTest extends TestCase
{
    public function test_it_creates_pending_status(): void
    {
        $status = PaymentStatus::pending();

        $this->assertEquals('pending', $status->value());
        $this->assertTrue($status->isPending());
        $this->assertFalse($status->isPaid());
        $this->assertFalse($status->isOverdue());
        $this->assertFalse($status->isCancelled());
    }

    public function test_it_creates_paid_status(): void
    {
        $status = PaymentStatus::paid();

        $this->assertEquals('paid', $status->value());
        $this->assertTrue($status->isPaid());
        $this->assertFalse($status->isPending());
    }

    public function test_it_creates_overdue_status(): void
    {
        $status = PaymentStatus::overdue();

        $this->assertEquals('overdue', $status->value());
        $this->assertTrue($status->isOverdue());
        $this->assertFalse($status->isPending());
    }

    public function test_it_creates_cancelled_status(): void
    {
        $status = PaymentStatus::cancelled();

        $this->assertEquals('cancelled', $status->value());
        $this->assertTrue($status->isCancelled());
        $this->assertFalse($status->isPending());
    }

    public function test_it_creates_from_string(): void
    {
        $status = PaymentStatus::fromString('pending');

        $this->assertTrue($status->isPending());
    }

    public function test_it_throws_exception_for_invalid_status(): void
    {
        $this->expectException(InvalidPaymentStatusException::class);
        $this->expectExceptionMessage('Invalid payment status: invalid');

        PaymentStatus::fromString('invalid');
    }

    public function test_it_checks_if_can_pay(): void
    {
        $this->assertTrue(PaymentStatus::pending()->canPay());
        $this->assertTrue(PaymentStatus::overdue()->canPay());
        $this->assertFalse(PaymentStatus::paid()->canPay());
        $this->assertFalse(PaymentStatus::cancelled()->canPay());
    }

    public function test_it_checks_if_can_cancel(): void
    {
        $this->assertTrue(PaymentStatus::pending()->canCancel());
        $this->assertTrue(PaymentStatus::overdue()->canCancel());
        $this->assertFalse(PaymentStatus::paid()->canCancel());
        $this->assertFalse(PaymentStatus::cancelled()->canCancel());
    }

    public function test_it_compares_equality(): void
    {
        $status1 = PaymentStatus::pending();
        $status2 = PaymentStatus::pending();
        $status3 = PaymentStatus::paid();

        $this->assertTrue($status1->equals($status2));
        $this->assertFalse($status1->equals($status3));
    }

    public function test_it_converts_to_string(): void
    {
        $this->assertEquals('Pendente', (string) PaymentStatus::pending());
        $this->assertEquals('Pago', (string) PaymentStatus::paid());
        $this->assertEquals('Vencido', (string) PaymentStatus::overdue());
        $this->assertEquals('Cancelado', (string) PaymentStatus::cancelled());
    }
}


