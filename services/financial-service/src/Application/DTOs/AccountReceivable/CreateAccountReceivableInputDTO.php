<?php

declare(strict_types=1);

namespace Src\Application\DTOs\AccountReceivable;

/**
 * CreateAccountReceivableInputDTO
 * 
 * DTO para criação de conta a receber.
 */
final class CreateAccountReceivableInputDTO
{
    public function __construct(
        public readonly string $customer_id,
        public readonly string $category_id,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $issue_date, // Y-m-d
        public readonly int $payment_terms_days
    ) {
    }

    /**
     * Cria a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customer_id: $data['customer_id'],
            category_id: $data['category_id'],
            description: $data['description'],
            amount: (float) $data['amount'],
            issue_date: $data['issue_date'],
            payment_terms_days: (int) $data['payment_terms_days']
        );
    }
}


