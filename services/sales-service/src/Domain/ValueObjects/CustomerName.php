<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidCustomerNameException;

/**
 * Customer Name Value Object
 * 
 * Representa o nome de um cliente com validação.
 */
final class CustomerName
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de uma string
     */
    public static function fromString(string $name): self
    {
        return new self(trim($name));
    }

    /**
     * Valida o nome
     */
    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidCustomerNameException('Customer name cannot be empty');
        }

        if (strlen($this->value) < 2) {
            throw new InvalidCustomerNameException('Customer name must be at least 2 characters long');
        }

        if (strlen($this->value) > 200) {
            throw new InvalidCustomerNameException('Customer name must not exceed 200 characters');
        }

        // Apenas letras, espaços, hífens, apóstrofos e pontos
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/u', $this->value)) {
            throw new InvalidCustomerNameException('Customer name contains invalid characters');
        }
    }

    /**
     * Retorna o valor
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Compara com outro nome
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
