<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
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

/**
 * AccountPayable Entity
 * 
 * Representa uma conta a pagar no domínio financeiro.
 */
final class AccountPayable
{
    /** @var array<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly AccountPayableId $id,
        private readonly SupplierId $supplierId,
        private readonly CategoryId $categoryId,
        private string $description,
        private readonly Money $amount,
        private readonly DateTimeImmutable $issueDate,
        private readonly DateTimeImmutable $dueDate,
        private PaymentStatus $status,
        private ?DateTimeImmutable $paidAt,
        private ?string $paymentNotes,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
        $this->validate();
    }

    /**
     * Cria uma nova conta a pagar
     */
    public static function create(
        SupplierId $supplierId,
        CategoryId $categoryId,
        string $description,
        Money $amount,
        DateTimeImmutable $issueDate,
        PaymentTerms $paymentTerms
    ): self {
        $now = new DateTimeImmutable();
        $dueDate = $paymentTerms->calculateDueDate($issueDate);

        $account = new self(
            id: AccountPayableId::generate(),
            supplierId: $supplierId,
            categoryId: $categoryId,
            description: $description,
            amount: $amount,
            issueDate: $issueDate,
            dueDate: $dueDate,
            status: PaymentStatus::pending(),
            paidAt: null,
            paymentNotes: null,
            createdAt: $now,
            updatedAt: $now
        );

        $account->recordDomainEvent(new AccountPayableCreated(
            accountPayableId: $account->id->value(),
            supplierId: $supplierId->value(),
            amount: $amount->toFloat(),
            dueDate: $dueDate,
            occurredOn: $now
        ));

        return $account;
    }

    /**
     * Reconstitui de dados persistidos
     */
    public static function reconstitute(
        AccountPayableId $id,
        SupplierId $supplierId,
        CategoryId $categoryId,
        string $description,
        Money $amount,
        DateTimeImmutable $issueDate,
        DateTimeImmutable $dueDate,
        PaymentStatus $status,
        ?DateTimeImmutable $paidAt,
        ?string $paymentNotes,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            supplierId: $supplierId,
            categoryId: $categoryId,
            description: $description,
            amount: $amount,
            issueDate: $issueDate,
            dueDate: $dueDate,
            status: $status,
            paidAt: $paidAt,
            paymentNotes: $paymentNotes,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    private function validate(): void
    {
        if ($this->amount->isZero()) {
            throw new InvalidAccountPayableException('Amount cannot be zero');
        }

        if ($this->dueDate < $this->issueDate) {
            throw new InvalidAccountPayableException('Due date cannot be before issue date');
        }

        if ($this->status->isPaid() && !$this->paidAt) {
            throw new InvalidAccountPayableException('Paid accounts must have a payment date');
        }
    }

    /**
     * Registra o pagamento da conta
     */
    public function pay(?string $notes = null): void
    {
        if (!$this->status->canPay()) {
            throw new InvalidAccountPayableException(
                "Cannot pay account with status: {$this->status->value()}"
            );
        }

        $this->status = PaymentStatus::paid();
        $this->paidAt = new DateTimeImmutable();
        $this->paymentNotes = $notes;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordDomainEvent(new AccountPayablePaid(
            accountPayableId: $this->id->value(),
            supplierId: $this->supplierId->value(),
            amount: $this->amount->toFloat(),
            paidAt: $this->paidAt,
            occurredOn: new DateTimeImmutable()
        ));
    }

    /**
     * Marca como vencida
     */
    public function markAsOverdue(): void
    {
        if (!$this->status->isPending()) {
            return; // Só marca como vencido se estiver pendente
        }

        $now = new DateTimeImmutable();
        
        if ($this->dueDate >= $now) {
            throw new InvalidAccountPayableException('Cannot mark as overdue before due date');
        }

        $this->status = PaymentStatus::overdue();
        $this->updatedAt = $now;

        $this->recordDomainEvent(new AccountPayableOverdue(
            accountPayableId: $this->id->value(),
            supplierId: $this->supplierId->value(),
            amount: $this->amount->toFloat(),
            dueDate: $this->dueDate,
            occurredOn: $now
        ));
    }

    /**
     * Cancela a conta
     */
    public function cancel(?string $reason = null): void
    {
        if (!$this->status->canCancel()) {
            throw new InvalidAccountPayableException(
                "Cannot cancel account with status: {$this->status->value()}"
            );
        }

        $this->status = PaymentStatus::cancelled();
        $this->paymentNotes = $reason;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Verifica se está vencida
     */
    public function isOverdue(): bool
    {
        return $this->status->isPending() && $this->dueDate < new DateTimeImmutable();
    }

    // Getters
    public function id(): AccountPayableId
    {
        return $this->id;
    }

    public function supplierId(): SupplierId
    {
        return $this->supplierId;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function issueDate(): DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function dueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function paymentNotes(): ?string
    {
        return $this->paymentNotes;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Domain Events
    private function recordDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<object>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}


