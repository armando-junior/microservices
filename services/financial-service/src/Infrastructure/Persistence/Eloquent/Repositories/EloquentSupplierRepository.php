<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Domain\Entities\Supplier;
use Src\Domain\Repositories\SupplierRepositoryInterface;
use Src\Domain\ValueObjects\SupplierId;
use Src\Domain\ValueObjects\SupplierName;
use Src\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

/**
 * EloquentSupplierRepository
 * 
 * Implementação do repositório de fornecedores usando Eloquent.
 */
class EloquentSupplierRepository implements SupplierRepositoryInterface
{
    public function save(Supplier $supplier): void
    {
        SupplierModel::updateOrCreate(
            ['id' => $supplier->id()->value()],
            [
                'name' => $supplier->name()->value(),
                'document' => $supplier->document(),
                'email' => $supplier->email(),
                'phone' => $supplier->phone(),
                'address' => $supplier->address(),
                'active' => $supplier->isActive(),
            ]
        );
    }

    public function findById(SupplierId $id): ?Supplier
    {
        $model = SupplierModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByDocument(string $document): ?Supplier
    {
        $model = SupplierModel::where('document', $document)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(): array
    {
        return SupplierModel::all()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findActive(): array
    {
        return SupplierModel::where('active', true)
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $result = SupplierModel::paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $result->items() ? array_map(fn($model) => $this->toDomain($model), $result->items()) : [],
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'per_page' => $result->perPage(),
        ];
    }

    public function delete(SupplierId $id): void
    {
        SupplierModel::where('id', $id->value())->delete();
    }

    public function existsByDocument(string $document): bool
    {
        return SupplierModel::where('document', $document)->exists();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomain(SupplierModel $model): Supplier
    {
        return Supplier::reconstitute(
            id: SupplierId::fromString($model->id),
            name: SupplierName::fromString($model->name),
            document: $model->document,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            active: $model->active,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}


