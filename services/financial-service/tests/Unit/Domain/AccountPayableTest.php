<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\AccountPayable;
use Src\Domain\Events\AccountPayableCreated;
use Src\Domain\Events\AccountPayablePaid;
use Src\Domain\Events\AccountPayableOverdue;
use Src\Domain\Exceptions\InvalidAccountPayableException;
use Src\Domain\ValueObjects\AccountPayableId;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\PaymentStatus;
use Src\Domain\ValueObjects\PaymentTerms;
use Src\Domain\ValueObjects\SupplierId;

class AccountPayableTest extends TestCase
{
    public function test_it_creates_account_payable_successfully(): void
    {
        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Fornecimento de materiais',
            amount: Money::fromFloat(1500.00),
            issueDate: new DateTimeImmutable('2025-01-01'),
            paymentTerms: PaymentTerms::net30()
        );

        $this->assertNotNull($account->id());
        $this->assertEquals('Fornecimento de materiais', $account->description());
        $this->assertEquals(1500.00, $account->amount()->toFloat());
        $this->assertEquals('2025-01-01', $account->issueDate()->format('Y-m-d'));
        $this->assertEquals('2025-01-31', $account->dueDate()->format('Y-m-d'));
        $this->assertTrue($account->status()->isPending());
        $this->assertNull($account->paidAt());
    }

    public function test_it_records_account_created_event(): void
    {
        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(100.00),
            issueDate: new DateTimeImmutable(),
            paymentTerms: PaymentTerms::immediate()
        );

        $events = $account->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(AccountPayableCreated::class, $events[0]);
    }

    public function test_it_throws_exception_for_zero_amount(): void
    {
        $this->expectException(InvalidAccountPayableException::class);
        $this->expectExceptionMessage('Amount cannot be zero');

        AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::zero(),
            issueDate: new DateTimeImmutable(),
            paymentTerms: PaymentTerms::immediate()
        );
    }

    public function test_it_pays_account_successfully(): void
    {
        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(500.00),
            issueDate: new DateTimeImmutable(),
            paymentTerms: PaymentTerms::net30()
        );

        $account->pay('Pagamento via transferência');

        $this->assertTrue($account->status()->isPaid());
        $this->assertNotNull($account->paidAt());
        $this->assertEquals('Pagamento via transferência', $account->paymentNotes());
    }

    public function test_it_records_paid_event(): void
    {
        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(500.00),
            issueDate: new DateTimeImmutable(),
            paymentTerms: PaymentTerms::net30()
        );

        $account->pullDomainEvents(); // Limpa eventos anteriores
        $account->pay();

        $events = $account->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(AccountPayablePaid::class, $events[0]);
    }

    public function test_it_marks_as_overdue(): void
    {
        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(500.00),
            issueDate: new DateTimeImmutable('2025-01-01'),
            paymentTerms: PaymentTerms::net30()
        );

        $account->markAsOverdue();

        $this->assertTrue($account->status()->isOverdue());
    }

    public function test_it_records_overdue_event(): void
    {
        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(500.00),
            issueDate: new DateTimeImmutable('2025-01-01'),
            paymentTerms: PaymentTerms::net30()
        );

        $account->pullDomainEvents(); // Limpa eventos anteriores
        $account->markAsOverdue();

        $events = $account->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(AccountPayableOverdue::class, $events[0]);
    }

    public function test_it_cancels_account(): void
    {
        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(500.00),
            issueDate: new DateTimeImmutable(),
            paymentTerms: PaymentTerms::net30()
        );

        $account->cancel('Pedido cancelado');

        $this->assertTrue($account->status()->isCancelled());
        $this->assertEquals('Pedido cancelado', $account->paymentNotes());
    }

    public function test_it_throws_exception_when_paying_already_paid(): void
    {
        $this->expectException(InvalidAccountPayableException::class);

        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(500.00),
            issueDate: new DateTimeImmutable(),
            paymentTerms: PaymentTerms::net30()
        );

        $account->pay();
        $account->pay(); // Segunda tentativa deve falhar
    }

    public function test_it_throws_exception_when_cancelling_paid_account(): void
    {
        $this->expectException(InvalidAccountPayableException::class);

        $account = AccountPayable::create(
            supplierId: SupplierId::generate(),
            categoryId: CategoryId::generate(),
            description: 'Test',
            amount: Money::fromFloat(500.00),
            issueDate: new DateTimeImmutable(),
            paymentTerms: PaymentTerms::net30()
        );

        $account->pay();
        $account->cancel();
    }
}


