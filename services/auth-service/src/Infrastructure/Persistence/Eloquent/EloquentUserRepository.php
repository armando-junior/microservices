<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Src\Domain\Entities\User;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;
use Src\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Eloquent User Repository
 * 
 * Implementação do repositório de usuários usando Eloquent ORM.
 */
final class EloquentUserRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        UserModel::updateOrCreate(
            ['id' => $user->getId()->value()],
            [
                'name' => $user->getName()->value(),
                'email' => $user->getEmail()->value(),
                'password' => $user->getPassword()->hash(),
                'is_active' => $user->isActive(),
                'email_verified_at' => $user->getEmailVerifiedAt(),
                'created_at' => $user->getCreatedAt(),
                'updated_at' => $user->getUpdatedAt(),
            ]
        );
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::where('email', $email->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return UserModel::where('email', $email->value())->exists();
    }

    public function list(int $page = 1, int $perPage = 15): array
    {
        $models = UserModel::orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomain($model))->all();
    }

    public function count(): int
    {
        return UserModel::count();
    }

    public function delete(UserId $id): void
    {
        UserModel::where('id', $id->value())->delete();
    }

    public function findActive(int $page = 1, int $perPage = 15): array
    {
        $models = UserModel::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomain($model))->all();
    }

    public function findInactive(int $page = 1, int $perPage = 15): array
    {
        $models = UserModel::where('is_active', false)
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomain($model))->all();
    }

    /**
     * Converte Eloquent Model para Domain Entity
     */
    private function toDomain(UserModel $model): User
    {
        return User::reconstitute(
            id: UserId::fromString($model->id),
            name: new UserName($model->name),
            email: new Email($model->email),
            password: Password::fromHash($model->password),
            isActive: $model->is_active,
            createdAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: $model->updated_at ? new DateTimeImmutable($model->updated_at->toDateTimeString()) : null,
            emailVerifiedAt: $model->email_verified_at ? new DateTimeImmutable($model->email_verified_at->toDateTimeString()) : null
        );
    }
}

