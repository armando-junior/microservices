<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence;

use App\Models\Product as ProductModel;
use Src\Domain\Entities\Product;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\ProductName;
use Src\Domain\ValueObjects\SKU;
use Src\Domain\ValueObjects\Price;
use Src\Domain\ValueObjects\CategoryId;

/**
 * Eloquent Product Repository Implementation
 */
final class EloquentProductRepository implements ProductRepositoryInterface
{
    /**
     * Salva um produto
     */
    public function save(Product $product): void
    {
        ProductModel::updateOrCreate(
            ['id' => $product->getId()->value()],
            [
                'name' => $product->getName()->value(),
                'sku' => $product->getSku()->value(),
                'price' => $product->getPrice()->value(),
                'category_id' => $product->getCategoryId()?->value(),
                'barcode' => $product->getBarcode(),
                'description' => $product->getDescription(),
                'status' => $product->getStatus(),
            ]
        );
    }

    /**
     * Busca produto por ID
     */
    public function findById(ProductId $id): ?Product
    {
        $model = ProductModel::find($id->value());
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * Busca produto por SKU
     */
    public function findBySKU(SKU $sku): ?Product
    {
        $model = ProductModel::where('sku', $sku->value())->first();
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * Verifica se SKU existe
     */
    public function existsSKU(SKU $sku): bool
    {
        return ProductModel::where('sku', $sku->value())->exists();
    }

    /**
     * Lista produtos com filtros
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = ProductModel::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $models = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    /**
     * Busca produtos por termo
     */
    public function search(string $term, int $page = 1, int $perPage = 15): array
    {
        $models = ProductModel::where('name', 'ILIKE', "%{$term}%")
            ->orWhere('sku', 'ILIKE', "%{$term}%")
            ->orWhere('description', 'ILIKE', "%{$term}%")
            ->orderBy('name', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    /**
     * Deleta um produto
     */
    public function delete(ProductId $id): void
    {
        ProductModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomainEntity(ProductModel $model): Product
    {
        return Product::reconstitute(
            id: ProductId::fromString($model->id),
            name: ProductName::fromString($model->name),
            sku: SKU::fromString($model->sku),
            price: Price::fromFloat($model->price),
            categoryId: $model->category_id ? CategoryId::fromString($model->category_id) : null,
            barcode: $model->barcode,
            description: $model->description,
            status: $model->status,
            createdAt: \DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: $model->updated_at ? \DateTimeImmutable::createFromMutable($model->updated_at) : null
        );
    }
}

