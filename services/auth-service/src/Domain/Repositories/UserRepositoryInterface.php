<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\User;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;

/**
 * User Repository Interface
 * 
 * Define o contrato para persistência de usuários.
 * Implementações devem estar na camada de Infrastructure.
 */
interface UserRepositoryInterface
{
    /**
     * Salva ou atualiza um usuário
     */
    public function save(User $user): void;

    /**
     * Busca um usuário por ID
     * 
     * @return User|null
     */
    public function findById(UserId $id): ?User;

    /**
     * Busca um usuário por email
     * 
     * @return User|null
     */
    public function findByEmail(Email $email): ?User;

    /**
     * Verifica se um email já existe
     */
    public function existsByEmail(Email $email): bool;

    /**
     * Lista todos os usuários (com paginação)
     * 
     * @return User[]
     */
    public function list(int $page = 1, int $perPage = 15): array;

    /**
     * Conta o total de usuários
     */
    public function count(): int;

    /**
     * Deleta um usuário
     */
    public function delete(UserId $id): void;

    /**
     * Lista usuários ativos
     * 
     * @return User[]
     */
    public function findActive(int $page = 1, int $perPage = 15): array;

    /**
     * Lista usuários inativos
     * 
     * @return User[]
     */
    public function findInactive(int $page = 1, int $perPage = 15): array;
}

