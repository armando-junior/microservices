<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\Events\SupplierCreated;
use Src\Domain\Exceptions\InvalidSupplierException;
use Src\Domain\ValueObjects\SupplierId;
use Src\Domain\ValueObjects\SupplierName;

/**
 * Supplier Entity
 * 
 * Representa um fornecedor no domínio financeiro.
 */
final class Supplier
{
    /** @var array<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly SupplierId $id,
        private SupplierName $name,
        private ?string $document,
        private ?string $email,
        private ?string $phone,
        private ?string $address,
        private bool $active,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
        $this->validate();
    }

    /**
     * Cria um novo fornecedor
     */
    public static function create(
        SupplierName $name,
        ?string $document = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null
    ): self {
        $now = new DateTimeImmutable();
        
        $supplier = new self(
            id: SupplierId::generate(),
            name: $name,
            document: $document,
            email: $email,
            phone: $phone,
            address: $address,
            active: true,
            createdAt: $now,
            updatedAt: $now
        );

        $supplier->recordDomainEvent(new SupplierCreated(
            supplierId: $supplier->id->value(),
            name: $supplier->name->value(),
            occurredOn: $now
        ));

        return $supplier;
    }

    /**
     * Reconstitui de dados persistidos
     */
    public static function reconstitute(
        SupplierId $id,
        SupplierName $name,
        ?string $document,
        ?string $email,
        ?string $phone,
        ?string $address,
        bool $active,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            name: $name,
            document: $document,
            email: $email,
            phone: $phone,
            address: $address,
            active: $active,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    private function validate(): void
    {
        // Validar email se fornecido
        if ($this->email && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidSupplierException('Invalid email format');
        }

        // Validar documento se fornecido (pode ser CPF ou CNPJ)
        if ($this->document) {
            $cleanDocument = preg_replace('/\D/', '', $this->document);
            if ($cleanDocument && strlen($cleanDocument) !== 11 && strlen($cleanDocument) !== 14) {
                throw new InvalidSupplierException('Document must be CPF (11 digits) or CNPJ (14 digits)');
            }
        }
    }

    /**
     * Atualiza informações do fornecedor
     */
    public function update(
        SupplierName $name,
        ?string $document = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null
    ): void {
        $this->name = $name;
        $this->document = $document;
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
        $this->updatedAt = new DateTimeImmutable();
        
        $this->validate();
    }

    /**
     * Ativa o fornecedor
     */
    public function activate(): void
    {
        if ($this->active) {
            throw new InvalidSupplierException('Supplier is already active');
        }

        $this->active = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Desativa o fornecedor
     */
    public function deactivate(): void
    {
        if (!$this->active) {
            throw new InvalidSupplierException('Supplier is already inactive');
        }

        $this->active = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function id(): SupplierId
    {
        return $this->id;
    }

    public function name(): SupplierName
    {
        return $this->name;
    }

    public function document(): ?string
    {
        return $this->document;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Domain Events
    private function recordDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<object>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}


