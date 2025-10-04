<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;
use Src\Domain\ValueObjects\UserId;

/**
 * User Password Changed Event
 * 
 * Disparado quando um usuário altera sua senha.
 */
final class UserPasswordChanged implements DomainEvent
{
    private UserId $userId;
    private DateTimeImmutable $occurredOn;

    public function __construct(
        UserId $userId,
        DateTimeImmutable $occurredOn
    ) {
        $this->userId = $userId;
        $this->occurredOn = $occurredOn;
    }

    public function eventName(): string
    {
        return 'auth.user.password_changed';
    }

    public function routingKey(): string
    {
        return 'auth.user.password_changed';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'user_id' => $this->userId->value(),
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

