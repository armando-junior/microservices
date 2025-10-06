<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence;

use App\Models\Customer as CustomerModel;
use Src\Domain\Entities\Customer;
use Src\Domain\Repositories\CustomerRepositoryInterface;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;

/**
 * Eloquent Customer Repository
 * 
 * Implementação do repositório usando Eloquent ORM.
 */
final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function save(Customer $customer): void
    {
        CustomerModel::updateOrCreate(
            ['id' => $customer->getId()->value()],
            [
                'name' => $customer->getName()->value(),
                'email' => $customer->getEmail()->value(),
                'phone' => $customer->getPhone()->value(),
                'document' => $customer->getDocument()->value(),
                'address_street' => $customer->getAddressStreet(),
                'address_number' => $customer->getAddressNumber(),
                'address_complement' => $customer->getAddressComplement(),
                'address_city' => $customer->getAddressCity(),
                'address_state' => $customer->getAddressState(),
                'address_zip_code' => $customer->getAddressZipCode(),
                'status' => $customer->getStatus(),
                'updated_at' => $customer->getUpdatedAt(),
            ]
        );
    }

    public function findById(CustomerId $id): ?Customer
    {
        $model = CustomerModel::find($id->value());
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByEmail(Email $email): ?Customer
    {
        $model = CustomerModel::where('email', $email->value())->first();
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByDocument(Document $document): ?Customer
    {
        $model = CustomerModel::where('document', $document->value())->first();
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function existsEmail(Email $email): bool
    {
        return CustomerModel::where('email', $email->value())->exists();
    }

    public function existsDocument(Document $document): bool
    {
        return CustomerModel::where('document', $document->value())->exists();
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = CustomerModel::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('document', 'LIKE', "%{$search}%");
            });
        }

        $models = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    public function count(array $filters = []): int
    {
        $query = CustomerModel::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('document', 'LIKE', "%{$search}%");
            });
        }

        return $query->count();
    }

    public function delete(CustomerId $id): void
    {
        CustomerModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Model para Domain Entity
     */
    private function toDomainEntity(CustomerModel $model): Customer
    {
        return Customer::reconstitute(
            id: CustomerId::fromString($model->id),
            name: CustomerName::fromString($model->name),
            email: Email::fromString($model->email),
            phone: Phone::fromString($model->phone),
            document: Document::fromString($model->document),
            addressStreet: $model->address_street,
            addressNumber: $model->address_number,
            addressComplement: $model->address_complement,
            addressCity: $model->address_city,
            addressState: $model->address_state,
            addressZipCode: $model->address_zip_code,
            status: $model->status,
            createdAt: \DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: $model->updated_at ? \DateTimeImmutable::createFromMutable($model->updated_at) : null
        );
    }
}
