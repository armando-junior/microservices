<?php

declare(strict_types=1);

namespace Src\Domain\Exceptions;

use Exception;

/**
 * Base Domain Exception
 * 
 * Todas as exceções de domínio devem estender esta classe.
 */
abstract class DomainException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

