<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

/**
 * Invalid UserId Exception
 * 
 * Lançada quando um UserId inválido é fornecido.
 */
final class InvalidUserIdException extends DomainException
{
    public function __construct(string $message = 'Invalid user ID provided')
    {
        parent::__construct($message, 422);
    }
}

