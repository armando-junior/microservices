<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

use Exception;

class CategoryNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Category with ID {$id} not found");
    }
}


