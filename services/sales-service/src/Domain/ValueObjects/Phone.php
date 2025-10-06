<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidPhoneException;

/**
 * Phone Value Object
 * 
 * Representa um número de telefone válido (formato BR).
 */
final class Phone
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de uma string
     */
    public static function fromString(string $phone): self
    {
        // Remove formatação
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return new self($cleaned);
    }

    /**
     * Valida o telefone
     */
    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidPhoneException('Phone cannot be empty');
        }

        // Telefone BR: 10 ou 11 dígitos
        $length = strlen($this->value);
        if ($length < 10 || $length > 11) {
            throw new InvalidPhoneException('Phone must have 10 or 11 digits (BR format)');
        }

        if (!preg_match('/^[0-9]+$/', $this->value)) {
            throw new InvalidPhoneException('Phone must contain only digits');
        }
    }

    /**
     * Retorna o valor (apenas dígitos)
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Retorna formatado (11) 98765-4321
     */
    public function formatted(): string
    {
        if (strlen($this->value) === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($this->value, 0, 2),
                substr($this->value, 2, 5),
                substr($this->value, 7, 4)
            );
        }

        // 10 dígitos
        return sprintf(
            '(%s) %s-%s',
            substr($this->value, 0, 2),
            substr($this->value, 2, 4),
            substr($this->value, 6, 4)
        );
    }

    /**
     * Compara com outro telefone
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Conversão para string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
