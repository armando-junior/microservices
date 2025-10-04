<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

/**
 * Email Already Exists Exception
 * 
 * Lançada quando se tenta registrar um email que já existe.
 */
final class EmailAlreadyExistsException extends ApplicationException
{
    public function __construct(string $email)
    {
        parent::__construct("Email {$email} already exists", 409);
    }
}

