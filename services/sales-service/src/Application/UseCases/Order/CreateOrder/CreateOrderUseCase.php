<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\CreateOrder;

use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\CustomerNotFoundException;
use Src\Domain\Entities\Order;
use Src\Domain\Repositories\CustomerRepositoryInterface;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\OrderId;

/**
 * Create Order Use Case
 * 
 * Caso de uso para criar um novo pedido (draft).
 */
final class CreateOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(CreateOrderDTO $dto): OrderDTO
    {
        // 1. Criar Value Object para Customer ID
        $customerId = CustomerId::fromString($dto->customerId);

        // 2. Verificar se cliente existe
        $customer = $this->customerRepository->findById($customerId);
        if (!$customer) {
            throw CustomerNotFoundException::forId($dto->customerId);
        }

        // 3. Gerar próximo número de pedido
        $orderNumber = $this->orderRepository->nextOrderNumber();

        // 4. Criar entidade Order
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: $orderNumber,
            customerId: $customerId,
            notes: $dto->notes
        );

        // 5. Persistir
        $this->orderRepository->save($order);

        // 6. Retornar DTO
        return OrderDTO::fromEntity($order);
    }
}
