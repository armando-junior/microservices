<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidDocumentException;

/**
 * Document Value Object
 * 
 * Representa um CPF ou CNPJ (documentos brasileiros) válido.
 */
final class Document
{
    private const CPF_LENGTH = 11;
    private const CNPJ_LENGTH = 14;

    private function __construct(
        private readonly string $value,
        private readonly string $type  // 'CPF' ou 'CNPJ'
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de uma string
     */
    public static function fromString(string $document): self
    {
        // Remove formatação
        $cleaned = preg_replace('/[^0-9]/', '', $document);
        
        if (empty($cleaned)) {
            throw new InvalidDocumentException('Document cannot be empty');
        }

        $length = strlen($cleaned);
        $type = match ($length) {
            self::CPF_LENGTH => 'CPF',
            self::CNPJ_LENGTH => 'CNPJ',
            default => throw new InvalidDocumentException("Document must have 11 digits (CPF) or 14 digits (CNPJ). Got: $length")
        };

        return new self($cleaned, $type);
    }

    /**
     * Valida o documento (CPF ou CNPJ)
     */
    private function validate(): void
    {
        if ($this->type === 'CPF') {
            $this->validateCPF();
        } else {
            $this->validateCNPJ();
        }
    }

    /**
     * Valida CPF
     */
    private function validateCPF(): void
    {
        // Rejeita sequências conhecidas
        if (preg_match('/^(\d)\1+$/', $this->value)) {
            throw new InvalidDocumentException('Invalid CPF: sequence of same digits');
        }

        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += intval($this->value[$c]) * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if (intval($this->value[$c]) != $d) {
                throw new InvalidDocumentException('Invalid CPF');
            }
        }
    }

    /**
     * Valida CNPJ
     */
    private function validateCNPJ(): void
    {
        // Rejeita sequências conhecidas
        if (preg_match('/^(\d)\1+$/', $this->value)) {
            throw new InvalidDocumentException('Invalid CNPJ: sequence of same digits');
        }

        // Validação do primeiro dígito
        $sum = 0;
        $multipliers = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($this->value[$i]) * $multipliers[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if (intval($this->value[12]) != $digit1) {
            throw new InvalidDocumentException('Invalid CNPJ');
        }

        // Validação do segundo dígito
        $sum = 0;
        $multipliers = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($this->value[$i]) * $multipliers[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        if (intval($this->value[13]) != $digit2) {
            throw new InvalidDocumentException('Invalid CNPJ');
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
     * Retorna o tipo (CPF ou CNPJ)
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Retorna formatado (CPF: 123.456.789-01, CNPJ: 12.345.678/0001-90)
     */
    public function formatted(): string
    {
        if ($this->type === 'CPF') {
            return sprintf(
                '%s.%s.%s-%s',
                substr($this->value, 0, 3),
                substr($this->value, 3, 3),
                substr($this->value, 6, 3),
                substr($this->value, 9, 2)
            );
        }

        // CNPJ
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($this->value, 0, 2),
            substr($this->value, 2, 3),
            substr($this->value, 5, 3),
            substr($this->value, 8, 4),
            substr($this->value, 12, 2)
        );
    }

    /**
     * Verifica se é CPF
     */
    public function isCPF(): bool
    {
        return $this->type === 'CPF';
    }

    /**
     * Verifica se é CNPJ
     */
    public function isCNPJ(): bool
    {
        return $this->type === 'CNPJ';
    }

    /**
     * Compara com outro documento
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
