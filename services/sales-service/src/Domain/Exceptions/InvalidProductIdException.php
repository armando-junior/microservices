<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

/**
 * Invalid Product ID Exception
 */
final class InvalidProductIdException extends DomainException
{
    public static function withValue(string $value): self
    {
        return new self("Invalid product ID: {$value}");
    }
}
