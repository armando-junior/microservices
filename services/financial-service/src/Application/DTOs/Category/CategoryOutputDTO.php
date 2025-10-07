<?php

declare(strict_types=1);

namespace Src\Application\DTOs\Category;

use Src\Domain\Entities\Category;

/**
 * CategoryOutputDTO
 * 
 * DTO para saída de dados de categoria.
 */
final class CategoryOutputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $type,
        public readonly string $created_at,
        public readonly string $updated_at
    ) {
    }

    /**
     * Cria a partir de uma entidade
     */
    public static function fromEntity(Category $category): self
    {
        return new self(
            id: $category->id()->value(),
            name: $category->name(),
            description: $category->description(),
            type: $category->type()->value(),
            created_at: $category->createdAt()->format('Y-m-d H:i:s'),
            updated_at: $category->updatedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


