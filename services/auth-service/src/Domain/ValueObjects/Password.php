<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidPasswordException;

/**
 * Password Value Object
 * 
 * Representa uma senha no domínio.
 * Pode estar em formato plain text (para validação) ou hashed (para armazenamento).
 */
final class Password
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 72; // bcrypt limit

    private string $value;
    private bool $isHashed;

    private function __construct(string $password, bool $isHashed = false)
    {
        if (!$isHashed) {
            $this->validate($password);
        }
        
        $this->value = $password;
        $this->isHashed = $isHashed;
    }

    /**
     * Cria uma senha a partir de texto plano
     */
    public static function fromPlainText(string $plainPassword): self
    {
        return new self($plainPassword, false);
    }

    /**
     * Cria uma senha a partir de um hash
     */
    public static function fromHash(string $hashedPassword): self
    {
        return new self($hashedPassword, true);
    }

    /**
     * Valida a senha
     */
    private function validate(string $password): void
    {
        if (strlen($password) < self::MIN_LENGTH) {
            throw new InvalidPasswordException(
                sprintf('Password must be at least %d characters long', self::MIN_LENGTH)
            );
        }

        if (strlen($password) > self::MAX_LENGTH) {
            throw new InvalidPasswordException(
                sprintf('Password cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        // Validar complexidade (ao menos uma letra maiúscula, minúscula e número)
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidPasswordException('Password must contain at least one lowercase letter');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidPasswordException('Password must contain at least one uppercase letter');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidPasswordException('Password must contain at least one number');
        }
    }

    /**
     * Retorna o valor da senha (hash ou plain text)
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Verifica se a senha está hasheada
     */
    public function isHashed(): bool
    {
        return $this->isHashed;
    }

    /**
     * Hash da senha usando bcrypt
     */
    public function hash(): string
    {
        if ($this->isHashed) {
            return $this->value;
        }

        return password_hash($this->value, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verifica se a senha corresponde ao hash
     */
    public function matches(string $hashedPassword): bool
    {
        if ($this->isHashed) {
            throw new \LogicException('Cannot match an already hashed password');
        }

        return password_verify($this->value, $hashedPassword);
    }

    /**
     * Verifica se é igual a outra senha
     */
    public function equals(Password $other): bool
    {
        return $this->value === $other->value && $this->isHashed === $other->isHashed;
    }

    /**
     * Representação em string (sempre retorna o hash por segurança)
     */
    public function __toString(): string
    {
        return $this->hash();
    }
}

