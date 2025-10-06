<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

class DocumentAlreadyExistsException extends ApplicationException
{
    public static function withDocument(string $document): self
    {
        return new self("Document {$document} is already registered");
    }
}
