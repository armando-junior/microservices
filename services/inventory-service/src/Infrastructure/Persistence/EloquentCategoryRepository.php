<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence;

use App\Models\Category as CategoryModel;
use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;

/**
 * Eloquent Category Repository Implementation
 */
final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Salva uma categoria
     */
    public function save(Category $category): void
    {
        CategoryModel::updateOrCreate(
            ['id' => $category->getId()->value()],
            [
                'name' => $category->getName()->value(),
                'slug' => $category->getSlug(),
                'description' => $category->getDescription(),
                'status' => $category->getStatus(),
            ]
        );
    }

    /**
     * Busca categoria por ID
     */
    public function findById(CategoryId $id): ?Category
    {
        $model = CategoryModel::find($id->value());
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * Lista todas as categorias
     */
    public function findAll(string $status = 'active'): array
    {
        $models = CategoryModel::where('status', $status)
            ->orderBy('name', 'asc')
            ->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    /**
     * Deleta uma categoria
     */
    public function delete(CategoryId $id): void
    {
        CategoryModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomainEntity(CategoryModel $model): Category
    {
        return new Category(
            id: CategoryId::fromString($model->id),
            name: CategoryName::fromString($model->name),
            slug: $model->slug,
            description: $model->description,
            status: $model->status,
            createdAt: \DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: $model->updated_at ? \DateTimeImmutable::createFromMutable($model->updated_at) : null
        );
    }
}

