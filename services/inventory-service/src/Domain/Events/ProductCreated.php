<?php

declare(strict_types=1);

namespace Src\Domain\Events;

/**
 * Product Created Event
 */
final class ProductCreated extends DomainEvent
{
    public function eventName(): string
    {
        return 'product.created';
    }
}

