<?php

declare(strict_types=1);

namespace Src\Application\DTOs\AccountReceivable;

/**
 * ReceiveAccountReceivableInputDTO
 * 
 * DTO para recebimento de conta a receber.
 */
final class ReceiveAccountReceivableInputDTO
{
    public function __construct(
        public readonly string $account_receivable_id,
        public readonly ?string $notes = null
    ) {
    }

    /**
     * Cria a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            account_receivable_id: $data['account_receivable_id'],
            notes: $data['notes'] ?? null
        );
    }
}


