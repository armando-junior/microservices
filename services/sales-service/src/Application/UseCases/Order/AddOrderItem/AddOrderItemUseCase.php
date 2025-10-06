<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\AddOrderItem;

use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Application\Exceptions\ProductNotFoundException;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderItemId;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;
use Illuminate\Support\Facades\Http;

/**
 * Add Order Item Use Case
 * 
 * Adiciona um item ao pedido, buscando informações do produto
 * no Inventory Service.
 */
final class AddOrderItemUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(AddOrderItemDTO $dto): OrderDTO
    {
        // 1. Buscar pedido
        $orderId = OrderId::fromString($dto->orderId);
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw OrderNotFoundException::withId($dto->orderId);
        }

        // 2. Buscar informações do produto no Inventory Service
        $productData = $this->fetchProductFromInventory($dto->productId);

        // 3. Criar OrderItem
        $orderItem = \Src\Domain\Entities\OrderItem::create(
            id: OrderItemId::generate(),
            productId: $dto->productId, // Pass string directly
            productName: $productData['name'],
            sku: $productData['sku'],
            quantity: Quantity::fromInt($dto->quantity),
            unitPrice: Money::fromFloat($productData['price']),
            discount: $dto->discount ? Money::fromFloat($dto->discount) : null
        );

        // 4. Adicionar item ao pedido
        $order->addItem($orderItem);

        // 5. Persistir pedido
        $this->orderRepository->save($order);

        // 6. Retornar DTO
        return OrderDTO::fromEntity($order);
    }

    /**
     * Busca informações do produto no Inventory Service
     */
    private function fetchProductFromInventory(string $productId): array
    {
        try {
            // Chamar Inventory Service via HTTP
            $response = Http::timeout(5)
                ->get("http://inventory-service:8000/api/v1/products/{$productId}");

            if ($response->failed()) {
                if ($response->status() === 404) {
                    throw ProductNotFoundException::withId($productId);
                }
                throw new \RuntimeException("Failed to fetch product from Inventory Service");
            }

            $data = $response->json('data');
            
            return [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'price' => $data['price'],
            ];
        } catch (\Exception $e) {
            if ($e instanceof ProductNotFoundException) {
                throw $e;
            }
            throw new \RuntimeException("Error communicating with Inventory Service: " . $e->getMessage());
        }
    }
}
