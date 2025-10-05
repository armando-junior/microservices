<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\CreateCategory;

final class CreateCategoryDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null
    ) {
    }
}

