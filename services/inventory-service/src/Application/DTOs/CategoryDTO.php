<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

use Src\Domain\Entities\Category;

/**
 * Category Data Transfer Object
 */
final class CategoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $updatedAt
    ) {
    }

    /**
     * Cria um DTO a partir de uma entidade Category
     */
    public static function fromEntity(Category $category): self
    {
        return new self(
            id: $category->getId()->value(),
            name: $category->getName()->value(),
            slug: $category->getSlug(),
            description: $category->getDescription(),
            status: $category->getStatus(),
            createdAt: $category->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $category->getUpdatedAt()?->format('Y-m-d H:i:s')
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
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

