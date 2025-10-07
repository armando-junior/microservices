<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryType;

/**
 * Category Entity
 * 
 * Representa uma categoria financeira (receita ou despesa).
 */
final class Category
{
    private function __construct(
        private readonly CategoryId $id,
        private string $name,
        private ?string $description,
        private readonly CategoryType $type,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
    }

    /**
     * Cria uma nova categoria
     */
    public static function create(
        string $name,
        CategoryType $type,
        ?string $description = null
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: CategoryId::generate(),
            name: $name,
            description: $description,
            type: $type,
            createdAt: $now,
            updatedAt: $now
        );
    }

    /**
     * Reconstitui de dados persistidos
     */
    public static function reconstitute(
        CategoryId $id,
        string $name,
        ?string $description,
        CategoryType $type,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            name: $name,
            description: $description,
            type: $type,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Atualiza informações da categoria
     */
    public function update(string $name, ?string $description = null): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function id(): CategoryId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function type(): CategoryType
    {
        return $this->type;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}


