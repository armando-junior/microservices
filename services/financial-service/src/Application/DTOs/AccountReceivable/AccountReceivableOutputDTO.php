<?php

declare(strict_types=1);

namespace Src\Application\DTOs\AccountReceivable;

use Src\Domain\Entities\AccountReceivable;

/**
 * AccountReceivableOutputDTO
 * 
 * DTO para saída de dados de conta a receber.
 */
final class AccountReceivableOutputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $customer_id,
        public readonly string $category_id,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $issue_date,
        public readonly string $due_date,
        public readonly string $status,
        public readonly ?string $received_at,
        public readonly ?string $receiving_notes,
        public readonly string $created_at,
        public readonly string $updated_at
    ) {
    }

    /**
     * Cria a partir de uma entidade
     */
    public static function fromEntity(AccountReceivable $account): self
    {
        return new self(
            id: $account->id()->value(),
            customer_id: $account->customerId(),
            category_id: $account->categoryId()->value(),
            description: $account->description(),
            amount: $account->amount()->toFloat(),
            issue_date: $account->issueDate()->format('Y-m-d'),
            due_date: $account->dueDate()->format('Y-m-d'),
            status: $account->status()->value(),
            received_at: $account->receivedAt()?->format('Y-m-d H:i:s'),
            receiving_notes: $account->receivingNotes(),
            created_at: $account->createdAt()->format('Y-m-d H:i:s'),
            updated_at: $account->updatedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'received_at' => $this->received_at,
            'receiving_notes' => $this->receiving_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


