<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\ReserveStock;

use Illuminate\Support\Facades\DB;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;

/**
 * Reserve Stock Use Case
 * 
 * Reserva estoque para um pedido.
 * O estoque fica "bloqueado" mas ainda não é decrementado definitivamente.
 */
final class ReserveStockUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    public function execute(array $data): void
    {
        $productId = ProductId::fromString($data['product_id']);
        $quantity = (int) $data['quantity'];
        $orderId = $data['order_id'];
        $reference = $data['reference'] ?? "Order {$orderId}";

        // Verifica se produto existe
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new \DomainException("Product {$productId->value()} not found");
        }

        // Busca o estoque do produto
        $stock = $this->stockRepository->findByProductId($productId);
        if (!$stock) {
            throw new \DomainException("Stock not found for product {$productId->value()}");
        }

        // Verifica se há estoque disponível
        $availableQuantity = $this->getAvailableQuantity($stock->getId()->value());
        
        if ($availableQuantity < $quantity) {
            throw new \DomainException(
                "Insufficient stock for product {$productId->value()}. " .
                "Available: {$availableQuantity}, Required: {$quantity}"
            );
        }

        // Cria a reserva
        DB::table('stock_reservations')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'stock_id' => $stock->getId()->value(),
            'product_id' => $productId->value(),
            'order_id' => $orderId,
            'quantity' => $quantity,
            'reference' => $reference,
            'status' => 'pending',
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(15), // Expira em 15 minutos
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // TODO: Publicar evento inventory.stock.reserved
    }

    /**
     * Calcula quantidade disponível (total - reservas pendentes)
     */
    private function getAvailableQuantity(string $stockId): int
    {
        $stock = DB::table('stocks')->where('id', $stockId)->first();
        if (!$stock) {
            return 0;
        }

        $reservedQuantity = DB::table('stock_reservations')
            ->where('stock_id', $stockId)
            ->where('status', 'pending')
            ->sum('quantity');

        return max(0, $stock->quantity - $reservedQuantity);
    }
}

