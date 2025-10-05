<?php

declare(strict_types=1);

namespace Src\Domain\Events;

/**
 * Stock Low Alert Event
 */
final class StockLowAlert extends DomainEvent
{
    public function eventName(): string
    {
        return 'stock.low';
    }
}

