<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\UpdateCategory;

/**
 * Update Category DTO
 */
final readonly class UpdateCategoryDTO
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $status = null,
    ) {
    }
}
