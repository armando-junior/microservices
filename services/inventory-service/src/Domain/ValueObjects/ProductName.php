<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidProductNameException;

/**
 * Product Name Value Object
 * 
 * Representa o nome de um produto.
 * 
 * Regras:
 * - 3 a 200 caracteres
 * - Não pode conter apenas espaços
 */
final class ProductName
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 200;

    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo ProductName a partir de uma string
     */
    public static function fromString(string $name): self
    {
        return new self($name);
    }

    /**
     * Valida o nome do produto
     */
    private function validate(): void
    {
        $trimmed = trim($this->value);

        if (empty($trimmed)) {
            throw new InvalidProductNameException('Product name cannot be empty');
        }

        $length = mb_strlen($trimmed);
        
        if ($length < self::MIN_LENGTH) {
            throw new InvalidProductNameException(
                "Product name must be at least " . self::MIN_LENGTH . " characters long"
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidProductNameException(
                "Product name must not exceed " . self::MAX_LENGTH . " characters"
            );
        }
    }

    /**
     * Retorna o valor do nome (trimmed)
     */
    public function value(): string
    {
        return trim($this->value);
    }

    /**
     * Compara se dois nomes são iguais
     */
    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    /**
     * Converte para string
     */
    public function __toString(): string
    {
        return $this->value();
    }
}

