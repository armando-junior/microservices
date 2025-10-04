<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;
use Src\Domain\ValueObjects\UserId;

/**
 * User Updated Event
 * 
 * Disparado quando informações do usuário são atualizadas.
 */
final class UserUpdated implements DomainEvent
{
    private UserId $userId;
    private array $changes;
    private DateTimeImmutable $occurredOn;

    public function __construct(
        UserId $userId,
        array $changes,
        DateTimeImmutable $occurredOn
    ) {
        $this->userId = $userId;
        $this->changes = $changes;
        $this->occurredOn = $occurredOn;
    }

    public function eventName(): string
    {
        return 'auth.user.updated';
    }

    public function routingKey(): string
    {
        return 'auth.user.updated';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'user_id' => $this->userId->value(),
                'changes' => $this->changes,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

