<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

/**
 * User Not Found Exception
 * 
 * Lançada quando um usuário não é encontrado.
 */
final class UserNotFoundException extends ApplicationException
{
    public function __construct(string $identifier = '')
    {
        $message = $identifier
            ? "User with identifier {$identifier} not found"
            : 'User not found';
            
        parent::__construct($message, 404);
    }
}

