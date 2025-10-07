<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Src\Domain\Entities\AccountReceivable;
use Src\Domain\Repositories\AccountReceivableRepositoryInterface;
use Src\Domain\ValueObjects\AccountReceivableId;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\ReceivableStatus;
use Src\Infrastructure\Persistence\Eloquent\Models\AccountReceivableModel;

/**
 * EloquentAccountReceivableRepository
 * 
 * Implementação do repositório de contas a receber usando Eloquent.
 */
class EloquentAccountReceivableRepository implements AccountReceivableRepositoryInterface
{
    public function save(AccountReceivable $account): void
    {
        AccountReceivableModel::updateOrCreate(
            ['id' => $account->id()->value()],
            [
                'customer_id' => $account->customerId(),
                'category_id' => $account->categoryId()->value(),
                'description' => $account->description(),
                'amount_cents' => $account->amount()->cents(),
                'issue_date' => $account->issueDate(),
                'due_date' => $account->dueDate(),
                'status' => $account->status()->value(),
                'received_at' => $account->receivedAt(),
                'receiving_notes' => $account->receivingNotes(),
            ]
        );
    }

    public function findById(AccountReceivableId $id): ?AccountReceivable
    {
        $model = AccountReceivableModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(): array
    {
        return AccountReceivableModel::all()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findByCustomer(string $customerId): array
    {
        return AccountReceivableModel::where('customer_id', $customerId)
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findByStatus(ReceivableStatus $status): array
    {
        return AccountReceivableModel::where('status', $status->value())
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findOverdueUntil(DateTimeImmutable $date): array
    {
        return AccountReceivableModel::where('status', 'pending')
            ->where('due_date', '<=', $date)
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function findDueBetween(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return AccountReceivableModel::whereBetween('due_date', [$startDate, $endDate])
            ->get()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $query = AccountReceivableModel::query();

        // Aplica filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
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

    public function delete(AccountReceivableId $id): void
    {
        AccountReceivableModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomain(AccountReceivableModel $model): AccountReceivable
    {
        return AccountReceivable::reconstitute(
            id: AccountReceivableId::fromString($model->id),
            customerId: $model->customer_id,
            categoryId: CategoryId::fromString($model->category_id),
            description: $model->description,
            amount: Money::fromCents($model->amount_cents),
            issueDate: $model->issue_date->toDateTimeImmutable(),
            dueDate: $model->due_date->toDateTimeImmutable(),
            status: ReceivableStatus::fromString($model->status),
            receivedAt: $model->received_at?->toDateTimeImmutable(),
            receivingNotes: $model->receiving_notes,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}


