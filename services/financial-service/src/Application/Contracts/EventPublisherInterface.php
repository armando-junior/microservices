<?php

declare(strict_types=1);

namespace Src\Application\Contracts;

/**
 * EventPublisherInterface
 * 
 * Contrato para publicação de eventos de domínio.
 */
interface EventPublisherInterface
{
    /**
     * Publica um único evento
     */
    public function publish(object $event): void;

    /**
     * Publica múltiplos eventos
     * 
     * @param array<object> $events
     */
    public function publishAll(array $events): void;
}


