<?php

declare(strict_types=1);

namespace Src\Domain\Events;

/**
 * Stock Depleted Event
 */
final class StockDepleted extends DomainEvent
{
    public function eventName(): string
    {
        return 'stock.depleted';
    }
}

