<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Src\Domain\Entities\AccountPayable;
use Src\Domain\Repositories\AccountPayableRepositoryInterface;
use Src\Domain\ValueObjects\AccountPayableId;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\PaymentStatus;
use Src\Domain\ValueObjects\SupplierId;
use Src\Infrastructure\Persistence\Eloquent\Models\AccountPayableModel;

/**
 * EloquentAccountPayableRepository
 * 
 * Implementação do repositório de contas a pagar usando Eloquent.
 */
class EloquentAccountPayableRepository implements AccountPayableRepositoryInterface
{
    public function save(AccountPayable $account): void
    {
        AccountPayableModel::updateOrCreate(
            ['id' => $account->id()->value()],
            [
                'supplier_id' => $account->supplierId()->value(),
                'category_id' => $account->categoryId()->value(),
                'description' => $account->description(),
                'amount_cents' => $account->amount()->cents(),
                'issue_date' => $account->issueDate(),
                'due_date' => $account->dueDate(),
                'status' => $account->status()->value(),
                'paid_at' => $account->paidAt(),
                'payment_notes' => $account->paymentNotes(),
            ]
        );
    }

    public function findById(AccountPayableId $id): ?AccountPayable
    {
        $model = AccountPayableModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(): array
    {
        return AccountPayableModel::all()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findBySupplier(SupplierId $supplierId): array
    {
        return AccountPayableModel::where('supplier_id', $supplierId->value())
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findByStatus(PaymentStatus $status): array
    {
        return AccountPayableModel::where('status', $status->value())
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findOverdueUntil(DateTimeImmutable $date): array
    {
        return AccountPayableModel::where('status', 'pending')
            ->where('due_date', '<=', $date)
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findDueBetween(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return AccountPayableModel::whereBetween('due_date', [$startDate, $endDate])
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $query = AccountPayableModel::query();

        // Aplica filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (isset($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $result->items() ? array_map(fn($model) => $this->toDomain($model), $result->items()) : [],
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'per_page' => $result->perPage(),
        ];
    }

    public function delete(AccountPayableId $id): void
    {
        AccountPayableModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomain(AccountPayableModel $model): AccountPayable
    {
        return AccountPayable::reconstitute(
            id: AccountPayableId::fromString($model->id),
            supplierId: SupplierId::fromString($model->supplier_id),
            categoryId: CategoryId::fromString($model->category_id),
            description: $model->description,
            amount: Money::fromCents($model->amount_cents),
            issueDate: $model->issue_date->toDateTimeImmutable(),
            dueDate: $model->due_date->toDateTimeImmutable(),
            status: PaymentStatus::fromString($model->status),
            paidAt: $model->paid_at?->toDateTimeImmutable(),
            paymentNotes: $model->payment_notes,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}


