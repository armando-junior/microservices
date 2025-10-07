<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\Events\AccountReceivableCreated;
use Src\Domain\Events\AccountReceivableReceived;
use Src\Domain\Events\AccountReceivableOverdue;
use Src\Domain\Exceptions\InvalidAccountReceivableException;
use Src\Domain\ValueObjects\AccountReceivableId;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\PaymentTerms;
use Src\Domain\ValueObjects\ReceivableStatus;

/**
 * AccountReceivable Entity
 * 
 * Representa uma conta a receber no domínio financeiro.
 */
final class AccountReceivable
{
    /** @var array<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly AccountReceivableId $id,
        private readonly string $customerId, // ID do cliente (pode vir do Sales Service)
        private readonly CategoryId $categoryId,
        private string $description,
        private readonly Money $amount,
        private readonly DateTimeImmutable $issueDate,
        private readonly DateTimeImmutable $dueDate,
        private ReceivableStatus $status,
        private ?DateTimeImmutable $receivedAt,
        private ?string $receivingNotes,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
        $this->validate();
    }

    /**
     * Cria uma nova conta a receber
     */
    public static function create(
        string $customerId,
        CategoryId $categoryId,
        string $description,
        Money $amount,
        DateTimeImmutable $issueDate,
        PaymentTerms $paymentTerms
    ): self {
        $now = new DateTimeImmutable();
        $dueDate = $paymentTerms->calculateDueDate($issueDate);

        $account = new self(
            id: AccountReceivableId::generate(),
            customerId: $customerId,
            categoryId: $categoryId,
            description: $description,
            amount: $amount,
            issueDate: $issueDate,
            dueDate: $dueDate,
            status: ReceivableStatus::pending(),
            receivedAt: null,
            receivingNotes: null,
            createdAt: $now,
            updatedAt: $now
        );

        $account->recordDomainEvent(new AccountReceivableCreated(
            accountReceivableId: $account->id->value(),
            customerId: $customerId,
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
        AccountReceivableId $id,
        string $customerId,
        CategoryId $categoryId,
        string $description,
        Money $amount,
        DateTimeImmutable $issueDate,
        DateTimeImmutable $dueDate,
        ReceivableStatus $status,
        ?DateTimeImmutable $receivedAt,
        ?string $receivingNotes,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            customerId: $customerId,
            categoryId: $categoryId,
            description: $description,
            amount: $amount,
            issueDate: $issueDate,
            dueDate: $dueDate,
            status: $status,
            receivedAt: $receivedAt,
            receivingNotes: $receivingNotes,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    private function validate(): void
    {
        if ($this->amount->isZero()) {
            throw new InvalidAccountReceivableException('Amount cannot be zero');
        }

        if ($this->dueDate < $this->issueDate) {
            throw new InvalidAccountReceivableException('Due date cannot be before issue date');
        }

        if ($this->status->isReceived() && !$this->receivedAt) {
            throw new InvalidAccountReceivableException('Received accounts must have a receiving date');
        }
    }

    /**
     * Registra o recebimento da conta
     */
    public function receive(?string $notes = null): void
    {
        if (!$this->status->canReceive()) {
            throw new InvalidAccountReceivableException(
                "Cannot receive account with status: {$this->status->value()}"
            );
        }

        $this->status = ReceivableStatus::received();
        $this->receivedAt = new DateTimeImmutable();
        $this->receivingNotes = $notes;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordDomainEvent(new AccountReceivableReceived(
            accountReceivableId: $this->id->value(),
            customerId: $this->customerId,
            amount: $this->amount->toFloat(),
            receivedAt: $this->receivedAt,
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
            throw new InvalidAccountReceivableException('Cannot mark as overdue before due date');
        }

        $this->status = ReceivableStatus::overdue();
        $this->updatedAt = $now;

        $this->recordDomainEvent(new AccountReceivableOverdue(
            accountReceivableId: $this->id->value(),
            customerId: $this->customerId,
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
            throw new InvalidAccountReceivableException(
                "Cannot cancel account with status: {$this->status->value()}"
            );
        }

        $this->status = ReceivableStatus::cancelled();
        $this->receivingNotes = $reason;
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
    public function id(): AccountReceivableId
    {
        return $this->id;
    }

    public function customerId(): string
    {
        return $this->customerId;
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

    public function status(): ReceivableStatus
    {
        return $this->status;
    }

    public function receivedAt(): ?DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function receivingNotes(): ?string
    {
        return $this->receivingNotes;
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


