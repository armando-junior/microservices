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
    public function __construct()
    {
        parent::__construct('Invalid credentials provided', 401);
    }
}

