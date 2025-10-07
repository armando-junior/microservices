<?php

declare(strict_types=1);

namespace Src\Application\DTOs\Category;

/**
 * CreateCategoryInputDTO
 * 
 * DTO para criação de categoria.
 */
final class CreateCategoryInputDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type, // 'income' or 'expense'
        public readonly ?string $description = null
    ) {
    }

    /**
     * Cria a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            description: $data['description'] ?? null
        );
    }
}


