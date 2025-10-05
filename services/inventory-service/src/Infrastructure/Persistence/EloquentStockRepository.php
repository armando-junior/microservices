<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence;

use App\Models\Stock as StockModel;
use App\Models\StockMovement as StockMovementModel;
use Src\Domain\Entities\Stock;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\StockId;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;

/**
 * Eloquent Stock Repository Implementation
 */
final class EloquentStockRepository implements StockRepositoryInterface
{
    /**
     * Salva um stock
     */
    public function save(Stock $stock): void
    {
        StockModel::updateOrCreate(
            ['id' => $stock->getId()->value()],
            [
                'product_id' => $stock->getProductId()->value(),
                'quantity' => $stock->getQuantity()->value(),
                'minimum_quantity' => $stock->getMinimumQuantity()->value(),
                'maximum_quantity' => $stock->getMaximumQuantity()?->value(),
                'last_movement_at' => $stock->getLastMovementAt(),
            ]
        );
    }

    /**
     * Busca stock por ID
     */
    public function findById(StockId $id): ?Stock
    {
        $model = StockModel::find($id->value());
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * Busca stock por Product ID
     */
    public function findByProductId(ProductId $productId): ?Stock
    {
        $model = StockModel::where('product_id', $productId->value())->first();
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * Lista produtos com estoque baixo
     */
    public function findLowStock(): array
    {
        $models = StockModel::whereColumn('quantity', '<=', 'minimum_quantity')
            ->where('quantity', '>', 0)
            ->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    /**
     * Lista produtos esgotados
     */
    public function findDepleted(): array
    {
        $models = StockModel::where('quantity', 0)->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    /**
     * Salva movimentações de estoque
     */
    public function saveMovements(ProductId $productId, array $movements): void
    {
        $stock = StockModel::where('product_id', $productId->value())->first();
        
        if (!$stock) {
            return;
        }

        foreach ($movements as $movement) {
            StockMovementModel::create([
                'id' => $movement['id'],
                'stock_id' => $stock->id,
                'type' => $movement['type'],
                'quantity' => $movement['quantity'],
                'previous_quantity' => $movement['previous_quantity'],
                'new_quantity' => $movement['new_quantity'],
                'reason' => $movement['reason'],
                'reference_id' => $movement['reference_id'] ?? null,
                'created_at' => $movement['created_at'],
            ]);
        }
    }

    /**
     * Busca movimentações de um produto
     */
    public function getMovements(ProductId $productId, int $limit = 50): array
    {
        $stock = StockModel::where('product_id', $productId->value())->first();
        
        if (!$stock) {
            return [];
        }

        return StockMovementModel::where('stock_id', $stock->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Deleta um stock
     */
    public function delete(StockId $id): void
    {
        StockModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomainEntity(StockModel $model): Stock
    {
        return new Stock(
            id: StockId::fromString($model->id),
            productId: ProductId::fromString($model->product_id),
            quantity: Quantity::fromInt($model->quantity),
            minimumQuantity: Quantity::fromInt($model->minimum_quantity),
            maximumQuantity: $model->maximum_quantity ? Quantity::fromInt($model->maximum_quantity) : null,
            lastMovementAt: $model->last_movement_at ? \DateTimeImmutable::createFromMutable($model->last_movement_at) : null,
            createdAt: \DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: $model->updated_at ? \DateTimeImmutable::createFromMutable($model->updated_at) : null
        );
    }
}

