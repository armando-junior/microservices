<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;

/**
 * Customer Entity (Aggregate Root)
 * 
 * Representa um cliente no sistema de vendas.
 */
final class Customer
{
    private const STATUS_ACTIVE = 'active';
    private const STATUS_INACTIVE = 'inactive';

    private array $domainEvents = [];

    private function __construct(
        private readonly CustomerId $id,
        private CustomerName $name,
        private Email $email,
        private Phone $phone,
        private Document $document,
        private ?string $addressStreet = null,
        private ?string $addressNumber = null,
        private ?string $addressComplement = null,
        private ?string $addressCity = null,
        private ?string $addressState = null,
        private ?string $addressZipCode = null,
        private string $status = self::STATUS_ACTIVE,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = null
    ) {
    }

    /**
     * Cria um novo cliente
     */
    public static function create(
        CustomerId $id,
        CustomerName $name,
        Email $email,
        Phone $phone,
        Document $document,
        ?string $addressStreet = null,
        ?string $addressNumber = null,
        ?string $addressComplement = null,
        ?string $addressCity = null,
        ?string $addressState = null,
        ?string $addressZipCode = null
    ): self {
        $customer = new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            document: $document,
            addressStreet: $addressStreet,
            addressNumber: $addressNumber,
            addressComplement: $addressComplement,
            addressCity: $addressCity,
            addressState: $addressState,
            addressZipCode: $addressZipCode,
            status: self::STATUS_ACTIVE,
            createdAt: new DateTimeImmutable()
        );

        $customer->recordEvent('CustomerCreated', [
            'customer_id' => $id->value(),
            'name' => $name->value(),
            'email' => $email->value(),
            'document' => $document->value(),
        ]);

        return $customer;
    }

    /**
     * Reconstitui um cliente do banco de dados
     */
    public static function reconstitute(
        CustomerId $id,
        CustomerName $name,
        Email $email,
        Phone $phone,
        Document $document,
        ?string $addressStreet,
        ?string $addressNumber,
        ?string $addressComplement,
        ?string $addressCity,
        ?string $addressState,
        ?string $addressZipCode,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            document: $document,
            addressStreet: $addressStreet,
            addressNumber: $addressNumber,
            addressComplement: $addressComplement,
            addressCity: $addressCity,
            addressState: $addressState,
            addressZipCode: $addressZipCode,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Atualiza informações básicas
     */
    public function updateInfo(
        CustomerName $name,
        Email $email,
        Phone $phone
    ): void {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->touch();
    }

    /**
     * Atualiza endereço
     */
    public function updateAddress(
        ?string $street,
        ?string $number,
        ?string $complement,
        ?string $city,
        ?string $state,
        ?string $zipCode
    ): void {
        $this->addressStreet = $street;
        $this->addressNumber = $number;
        $this->addressComplement = $complement;
        $this->addressCity = $city;
        $this->addressState = $state;
        $this->addressZipCode = $zipCode;
        $this->touch();
    }

    /**
     * Ativa o cliente
     */
    public function activate(): void
    {
        if ($this->isActive()) {
            return;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->touch();

        $this->recordEvent('CustomerActivated', [
            'customer_id' => $this->id->value(),
        ]);
    }

    /**
     * Desativa o cliente
     */
    public function deactivate(): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->status = self::STATUS_INACTIVE;
        $this->touch();

        $this->recordEvent('CustomerDeactivated', [
            'customer_id' => $this->id->value(),
        ]);
    }

    /**
     * Verifica se o cliente está ativo
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Getters
     */
    public function getId(): CustomerId
    {
        return $this->id;
    }

    public function getName(): CustomerName
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPhone(): Phone
    {
        return $this->phone;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    public function getAddressNumber(): ?string
    {
        return $this->addressNumber;
    }

    public function getAddressComplement(): ?string
    {
        return $this->addressComplement;
    }

    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    public function getAddressState(): ?string
    {
        return $this->addressState;
    }

    public function getAddressZipCode(): ?string
    {
        return $this->addressZipCode;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Registra um evento de domínio
     */
    private function recordEvent(string $eventName, array $payload = []): void
    {
        $this->domainEvents[] = [
            'event' => $eventName,
            'payload' => $payload,
            'occurred_at' => new DateTimeImmutable(),
        ];
    }

    /**
     * Puxa e limpa os eventos de domínio
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    /**
     * Atualiza a data de modificação
     */
    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
