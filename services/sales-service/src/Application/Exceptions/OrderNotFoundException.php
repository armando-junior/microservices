<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

class OrderNotFoundException extends ApplicationException
{
    public static function forId(string $id): self
    {
        return new self("Order with ID {$id} not found");
    }

    public static function forNumber(string $number): self
    {
        return new self("Order with number {$number} not found");
    }
}
