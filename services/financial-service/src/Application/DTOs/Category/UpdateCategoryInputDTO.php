<?php

declare(strict_types=1);

namespace Src\Application\DTOs\Category;

/**
 * UpdateCategoryInputDTO
 * 
 * DTO para atualização de categoria.
 */
final class UpdateCategoryInputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description = null
    ) {
    }

    /**
     * Cria a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'] ?? null
        );
    }
}


