<?php

declare(strict_types=1);

namespace Src\Application\UseCases\AccountPayable\CreateAccountPayable;

use DateTimeImmutable;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\AccountPayable\AccountPayableOutputDTO;
use Src\Application\DTOs\AccountPayable\CreateAccountPayableInputDTO;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Application\Exceptions\SupplierNotFoundException;
use Src\Domain\Entities\AccountPayable;
use Src\Domain\Repositories\AccountPayableRepositoryInterface;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\Repositories\SupplierRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\PaymentTerms;
use Src\Domain\ValueObjects\SupplierId;

/**
 * CreateAccountPayableUseCase
 * 
 * Caso de uso para criação de conta a pagar.
 */
final class CreateAccountPayableUseCase
{
    public function __construct(
        private readonly AccountPayableRepositoryInterface $accountPayableRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(CreateAccountPayableInputDTO $input): AccountPayableOutputDTO
    {
        // Valida se o fornecedor existe
        $supplier = $this->supplierRepository->findById(
            SupplierId::fromString($input->supplier_id)
        );

        if (!$supplier) {
            throw SupplierNotFoundException::withId($input->supplier_id);
        }

        // Valida se a categoria existe
        $category = $this->categoryRepository->findById(
            CategoryId::fromString($input->category_id)
        );

        if (!$category) {
            throw CategoryNotFoundException::withId($input->category_id);
        }

        // Cria a conta a pagar
        $account = AccountPayable::create(
            supplierId: SupplierId::fromString($input->supplier_id),
            categoryId: CategoryId::fromString($input->category_id),
            description: $input->description,
            amount: Money::fromFloat($input->amount),
            issueDate: new DateTimeImmutable($input->issue_date),
            paymentTerms: PaymentTerms::days($input->payment_terms_days)
        );

        // Persiste
        $this->accountPayableRepository->save($account);

        // Publica eventos de domínio
        $this->eventPublisher->publishAll($account->pullDomainEvents());

        return AccountPayableOutputDTO::fromEntity($account);
    }
}


