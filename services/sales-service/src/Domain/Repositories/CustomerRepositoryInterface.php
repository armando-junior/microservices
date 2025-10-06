<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\Customer;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Document;

/**
 * Customer Repository Interface
 * 
 * Define o contrato para persistência de clientes.
 */
interface CustomerRepositoryInterface
{
    /**
     * Salva um cliente (create ou update)
     */
    public function save(Customer $customer): void;

    /**
     * Busca cliente por ID
     */
    public function findById(CustomerId $id): ?Customer;

    /**
     * Busca cliente por email
     */
    public function findByEmail(Email $email): ?Customer;

    /**
     * Busca cliente por documento
     */
    public function findByDocument(Document $document): ?Customer;

    /**
     * Verifica se email já existe
     */
    public function existsEmail(Email $email): bool;

    /**
     * Verifica se documento já existe
     */
    public function existsDocument(Document $document): bool;

    /**
     * Lista clientes com filtros
     * 
     * @param array $filters ['status' => 'active', 'search' => 'john']
     * @return Customer[]
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Conta total de clientes
     */
    public function count(array $filters = []): int;

    /**
     * Deleta um cliente
     */
    public function delete(CustomerId $id): void;
}
