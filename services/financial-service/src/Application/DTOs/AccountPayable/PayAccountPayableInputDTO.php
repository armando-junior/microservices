<?php

declare(strict_types=1);

namespace Src\Application\DTOs\AccountPayable;

/**
 * PayAccountPayableInputDTO
 * 
 * DTO para pagamento de conta a pagar.
 */
final class PayAccountPayableInputDTO
{
    public function __construct(
        public readonly string $account_payable_id,
        public readonly ?string $notes = null
    ) {
    }

    /**
     * Cria a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            account_payable_id: $data['account_payable_id'],
            notes: $data['notes'] ?? null
        );
    }
}


