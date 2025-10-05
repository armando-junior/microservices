<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Src\Domain\Exceptions\InvalidUserIdException;

/**
 * UserId Value Object
 * 
 * Representa um identificador único de usuário (UUID v4).
 */
final class UserId
{
    private string $value;

    private function __construct(string $id)
    {
        $this->validate($id);
        $this->value = strtolower($id); // Normalize to lowercase for consistency
    }

    /**
     * Gera um novo UserId (UUID v4)
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    /**
     * Cria UserId a partir de uma string UUID
     */
    public static function fromString(string $id): self
    {
        return new self($id);
    }

    /**
     * Cria UserId a partir de um UuidInterface
     */
    public static function fromUuid(UuidInterface $uuid): self
    {
        return new self($uuid->toString());
    }

    /**
     * Valida o UUID
     */
    private function validate(string $id): void
    {
        if (!Uuid::isValid($id)) {
            throw new InvalidUserIdException("Invalid UUID format: {$id}");
        }
    }

    /**
     * Retorna o valor do UUID
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Retorna como UuidInterface
     */
    public function toUuid(): UuidInterface
    {
        return Uuid::fromString($this->value);
    }

    /**
     * Verifica se é igual a outro UserId
     */
    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Representação em string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Serialização para JSON
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}

