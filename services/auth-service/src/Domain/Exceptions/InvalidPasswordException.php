<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

/**
 * Invalid Password Exception
 * 
 * Lançada quando uma senha inválida é fornecida.
 */
final class InvalidPasswordException extends DomainException
{
    public function __construct(string $message = 'Invalid password provided')
    {
        parent::__construct($message, 422);
    }
}

