<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

final class CategoryNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self("Category not found with ID: {$id}");
    }
}

