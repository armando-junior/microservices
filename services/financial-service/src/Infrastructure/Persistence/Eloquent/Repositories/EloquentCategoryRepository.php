<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryType;
use Src\Infrastructure\Persistence\Eloquent\Models\CategoryModel;

/**
 * EloquentCategoryRepository
 * 
 * Implementação do repositório de categorias usando Eloquent.
 */
class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function save(Category $category): void
    {
        CategoryModel::updateOrCreate(
            ['id' => $category->id()->value()],
            [
                'name' => $category->name(),
                'description' => $category->description(),
                'type' => $category->type()->value(),
            ]
        );
    }

    public function findById(CategoryId $id): ?Category
    {
        $model = CategoryModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(): array
    {
        return CategoryModel::all()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findByType(CategoryType $type): array
    {
        return CategoryModel::where('type', $type->value())
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function delete(CategoryId $id): void
    {
        CategoryModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomain(CategoryModel $model): Category
    {
        return Category::reconstitute(
            id: CategoryId::fromString($model->id),
            name: $model->name,
            description: $model->description,
            type: CategoryType::fromString($model->type),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}


