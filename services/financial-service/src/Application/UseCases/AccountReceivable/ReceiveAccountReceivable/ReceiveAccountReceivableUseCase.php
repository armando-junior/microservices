<?php

declare(strict_types=1);

namespace Src\Application\UseCases\AccountReceivable\ReceiveAccountReceivable;

use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\AccountReceivable\AccountReceivableOutputDTO;
use Src\Application\DTOs\AccountReceivable\ReceiveAccountReceivableInputDTO;
use Src\Application\Exceptions\AccountReceivableNotFoundException;
use Src\Domain\Repositories\AccountReceivableRepositoryInterface;
use Src\Domain\ValueObjects\AccountReceivableId;

/**
 * ReceiveAccountReceivableUseCase
 * 
 * Caso de uso para recebimento de conta a receber.
 */
final class ReceiveAccountReceivableUseCase
{
    public function __construct(
        private readonly AccountReceivableRepositoryInterface $accountReceivableRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(ReceiveAccountReceivableInputDTO $input): AccountReceivableOutputDTO
    {
        // Busca a conta a receber
        $account = $this->accountReceivableRepository->findById(
            AccountReceivableId::fromString($input->account_receivable_id)
        );

        if (!$account) {
            throw AccountReceivableNotFoundException::withId($input->account_receivable_id);
        }

        // Registra o recebimento
        $account->receive($input->notes);

        // Persiste
        $this->accountReceivableRepository->save($account);

        // Publica eventos de domínio
        $this->eventPublisher->publishAll($account->pullDomainEvents());

        return AccountReceivableOutputDTO::fromEntity($account);
    }
}


