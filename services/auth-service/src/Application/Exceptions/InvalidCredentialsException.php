<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

/**
 * Invalid Credentials Exception
 * 
 * Lançada quando as credenciais de login são inválidas.
 */
final class InvalidCredentialsException extends ApplicationException
{
    public function __construct(string $message = 'Invalid credentials provided')
    {
        parent::__construct($message, 401);
    }
}

