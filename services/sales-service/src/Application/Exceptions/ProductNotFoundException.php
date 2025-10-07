<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

class ProductNotFoundException extends ApplicationException
{
    public function __construct(string $id = '')
    {
        $message = $id 
            ? "Product with ID {$id} not found in Inventory Service"
            : "Product not found in Inventory Service";
            
        parent::__construct($message);
    }
    
    public static function forId(string $id): self
    {
        return new self($id);
    }
    
    public static function withId(string $id): self
    {
        return new self($id);
    }
}
