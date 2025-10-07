<?php

declare(strict_types=1);

namespace Src\Application\UseCases\AccountPayable\PayAccountPayable;

use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\AccountPayable\AccountPayableOutputDTO;
use Src\Application\DTOs\AccountPayable\PayAccountPayableInputDTO;
use Src\Application\Exceptions\AccountPayableNotFoundException;
use Src\Domain\Repositories\AccountPayableRepositoryInterface;
use Src\Domain\ValueObjects\AccountPayableId;

/**
 * PayAccountPayableUseCase
 * 
 * Caso de uso para pagamento de conta a pagar.
 */
final class PayAccountPayableUseCase
{
    public function __construct(
        private readonly AccountPayableRepositoryInterface $accountPayableRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(PayAccountPayableInputDTO $input): AccountPayableOutputDTO
    {
        // Busca a conta a pagar
        $account = $this->accountPayableRepository->findById(
            AccountPayableId::fromString($input->account_payable_id)
        );

        if (!$account) {
            throw AccountPayableNotFoundException::withId($input->account_payable_id);
        }

        // Registra o pagamento
        $account->pay($input->notes);

        // Persiste
        $this->accountPayableRepository->save($account);

        // Publica eventos de domínio
        $this->eventPublisher->publishAll($account->pullDomainEvents());

        return AccountPayableOutputDTO::fromEntity($account);
    }
}


