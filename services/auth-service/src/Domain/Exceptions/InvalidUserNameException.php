<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

/**
 * Invalid UserName Exception
 * 
 * Lançada quando um nome de usuário inválido é fornecido.
 */
final class InvalidUserNameException extends DomainException
{
    public function __construct(string $message = 'Invalid user name provided')
    {
        parent::__construct($message, 422);
    }
}

