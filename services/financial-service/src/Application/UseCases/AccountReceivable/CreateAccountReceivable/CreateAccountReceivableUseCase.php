<?php

declare(strict_types=1);

namespace Src\Application\UseCases\AccountReceivable\CreateAccountReceivable;

use DateTimeImmutable;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\AccountReceivable\AccountReceivableOutputDTO;
use Src\Application\DTOs\AccountReceivable\CreateAccountReceivableInputDTO;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Domain\Entities\AccountReceivable;
use Src\Domain\Repositories\AccountReceivableRepositoryInterface;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\PaymentTerms;

/**
 * CreateAccountReceivableUseCase
 * 
 * Caso de uso para criação de conta a receber.
 */
final class CreateAccountReceivableUseCase
{
    public function __construct(
        private readonly AccountReceivableRepositoryInterface $accountReceivableRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(CreateAccountReceivableInputDTO $input): AccountReceivableOutputDTO
    {
        // Valida se a categoria existe
        $category = $this->categoryRepository->findById(
            CategoryId::fromString($input->category_id)
        );

        if (!$category) {
            throw CategoryNotFoundException::withId($input->category_id);
        }

        // Cria a conta a receber
        $account = AccountReceivable::create(
            customerId: $input->customer_id,
            categoryId: CategoryId::fromString($input->category_id),
            description: $input->description,
            amount: Money::fromFloat($input->amount),
            issueDate: new DateTimeImmutable($input->issue_date),
            paymentTerms: PaymentTerms::days($input->payment_terms_days)
        );

        // Persiste
        $this->accountReceivableRepository->save($account);

        // Publica eventos de domínio
        $this->eventPublisher->publishAll($account->pullDomainEvents());

        return AccountReceivableOutputDTO::fromEntity($account);
    }
}


