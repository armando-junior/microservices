<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

/**
 * User Registered Event
 * 
 * Disparado quando um novo usuário é registrado no sistema.
 */
final class UserRegistered implements DomainEvent
{
    private UserId $userId;
    private Email $email;
    private UserName $name;
    private DateTimeImmutable $occurredOn;

    public function __construct(
        UserId $userId,
        Email $email,
        UserName $name,
        DateTimeImmutable $occurredOn
    ) {
        $this->userId = $userId;
        $this->email = $email;
        $this->name = $name;
        $this->occurredOn = $occurredOn;
    }

    public function eventName(): string
    {
        return 'auth.user.registered';
    }

    public function routingKey(): string
    {
        return 'auth.user.registered';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getName(): UserName
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'user_id' => $this->userId->value(),
                'email' => $this->email->value(),
                'name' => $this->name->value(),
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

