<?php

declare(strict_types=1);

namespace Src\Application\DTOs\AccountPayable;

use Src\Domain\Entities\AccountPayable;

/**
 * AccountPayableOutputDTO
 * 
 * DTO para saída de dados de conta a pagar.
 */
final class AccountPayableOutputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $supplier_id,
        public readonly string $category_id,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $issue_date,
        public readonly string $due_date,
        public readonly string $status,
        public readonly ?string $paid_at,
        public readonly ?string $payment_notes,
        public readonly string $created_at,
        public readonly string $updated_at
    ) {
    }

    /**
     * Cria a partir de uma entidade
     */
    public static function fromEntity(AccountPayable $account): self
    {
        return new self(
            id: $account->id()->value(),
            supplier_id: $account->supplierId()->value(),
            category_id: $account->categoryId()->value(),
            description: $account->description(),
            amount: $account->amount()->toFloat(),
            issue_date: $account->issueDate()->format('Y-m-d'),
            due_date: $account->dueDate()->format('Y-m-d'),
            status: $account->status()->value(),
            paid_at: $account->paidAt()?->format('Y-m-d H:i:s'),
            payment_notes: $account->paymentNotes(),
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
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'payment_notes' => $this->payment_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


