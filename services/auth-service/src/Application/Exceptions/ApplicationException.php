<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

use Exception;

/**
 * Base Application Exception
 * 
 * Todas as exceções da camada de aplicação devem estender esta classe.
 */
abstract class ApplicationException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

