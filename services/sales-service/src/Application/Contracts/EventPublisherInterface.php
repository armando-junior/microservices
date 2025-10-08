<?php

declare(strict_types=1);

namespace Src\Application\Contracts;

use Src\Domain\Events\DomainEvent;

/**
 * Event Publisher Interface
 * 
 * Interface para publicação de eventos de domínio.
 * Implementação deve estar na camada de Infrastructure (ex: RabbitMQ).
 */
interface EventPublisherInterface
{
    /**
     * Publica um evento de domínio
     */
    public function publish(DomainEvent $event): void;

    /**
     * Publica múltiplos eventos de domínio
     * 
     * @param DomainEvent[] $events
     */
    public function publishBatch(array $events): void;
}
