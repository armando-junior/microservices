<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

/**
 * Invalid Email Exception
 * 
 * Lançada quando um email inválido é fornecido.
 */
final class InvalidEmailException extends DomainException
{
    public function __construct(string $message = 'Invalid email provided')
    {
        parent::__construct($message, 422);
    }
}

