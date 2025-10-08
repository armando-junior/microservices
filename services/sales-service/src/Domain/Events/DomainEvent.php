<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Base Domain Event
 * 
 * Todos os eventos de domínio devem implementar esta interface.
 */
interface DomainEvent
{
    /**
     * Nome do evento
     */
    public function eventName(): string;

    /**
     * Routing key para RabbitMQ
     */
    public function routingKey(): string;

    /**
     * Data/hora que o evento ocorreu
     */
    public function occurredOn(): DateTimeImmutable;

    /**
     * Converte o evento para array (para serialização)
     */
    public function toArray(): array;

    /**
     * Converte o evento para JSON
     */
    public function toJson(): string;
}
