<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

class InvalidCustomerIdException extends DomainException
{
    public static function withValue(string $value): self
    {
        return new self("Invalid Customer ID: {$value}");
    }
}
