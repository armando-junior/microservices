<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\ProductName;
use Src\Domain\ValueObjects\SKU;
use Src\Domain\ValueObjects\Price;

/**
 * Product Entity (Aggregate Root)
 * 
 * Representa um produto no catálogo.
 */
final class Product
{
    private const STATUS_ACTIVE = 'active';
    private const STATUS_INACTIVE = 'inactive';
    private const STATUS_DISCONTINUED = 'discontinued';

    private array $domainEvents = [];

    private function __construct(
        private readonly ProductId $id,
        private ProductName $name,
        private SKU $sku,
        private Price $price,
        private ?CategoryId $categoryId = null,
        private ?string $barcode = null,
        private ?string $description = null,
        private string $status = self::STATUS_ACTIVE,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = null
    ) {
    }

    /**
     * Cria um novo produto
     */
    public static function create(
        ProductId $id,
        ProductName $name,
        SKU $sku,
        Price $price,
        ?CategoryId $categoryId = null,
        ?string $barcode = null,
        ?string $description = null
    ): self {
        $product = new self(
            id: $id,
            name: $name,
            sku: $sku,
            price: $price,
            categoryId: $categoryId,
            barcode: $barcode,
            description: $description,
            status: self::STATUS_ACTIVE,
            createdAt: new DateTimeImmutable()
        );

        // Registrar evento de domínio
        $product->recordEvent('ProductCreated', [
            'product_id' => $id->value(),
            'name' => $name->value(),
            'sku' => $sku->value(),
            'price' => $price->value(),
            'category_id' => $categoryId?->value(),
        ]);

        return $product;
    }

    /**
     * Reconstitui um produto do banco de dados
     */
    public static function reconstitute(
        ProductId $id,
        ProductName $name,
        SKU $sku,
        Price $price,
        ?CategoryId $categoryId,
        ?string $barcode,
        ?string $description,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            name: $name,
            sku: $sku,
            price: $price,
            categoryId: $categoryId,
            barcode: $barcode,
            description: $description,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Atualiza o nome do produto
     */
    public function updateName(ProductName $name): void
    {
        if ($this->name->equals($name)) {
            return;
        }

        $this->name = $name;
        $this->touch();

        $this->recordEvent('ProductUpdated', [
            'product_id' => $this->id->value(),
            'field' => 'name',
            'new_value' => $name->value(),
        ]);
    }

    /**
     * Atualiza o preço do produto
     */
    public function updatePrice(Price $price): void
    {
        if ($this->price->equals($price)) {
            return;
        }

        $oldPrice = $this->price;
        $this->price = $price;
        $this->touch();

        $this->recordEvent('PriceChanged', [
            'product_id' => $this->id->value(),
            'old_price' => $oldPrice->value(),
            'new_price' => $price->value(),
        ]);
    }

    /**
     * Atualiza a categoria do produto
     */
    public function updateCategory(?CategoryId $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->touch();
    }

    /**
     * Atualiza a descrição
     */
    public function updateDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    /**
     * Atualiza o código de barras
     */
    public function updateBarcode(?string $barcode): void
    {
        $this->barcode = $barcode;
        $this->touch();
    }

    /**
     * Ativa o produto
     */
    public function activate(): void
    {
        if ($this->isDiscontinued()) {
            throw new \DomainException('Cannot activate a discontinued product');
        }

        if ($this->isActive()) {
            return;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->touch();

        $this->recordEvent('ProductActivated', [
            'product_id' => $this->id->value(),
        ]);
    }

    /**
     * Desativa o produto
     */
    public function deactivate(): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->status = self::STATUS_INACTIVE;
        $this->touch();

        $this->recordEvent('ProductDeactivated', [
            'product_id' => $this->id->value(),
        ]);
    }

    /**
     * Descontinua o produto (ação irreversível)
     */
    public function discontinue(): void
    {
        if ($this->isDiscontinued()) {
            return;
        }

        $this->status = self::STATUS_DISCONTINUED;
        $this->touch();

        $this->recordEvent('ProductDiscontinued', [
            'product_id' => $this->id->value(),
        ]);
    }

    /**
     * Verifica se o produto está ativo
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica se o produto está inativo
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Verifica se o produto foi descontinuado
     */
    public function isDiscontinued(): bool
    {
        return $this->status === self::STATUS_DISCONTINUED;
    }

    /**
     * Atualiza o timestamp de updated_at
     */
    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Registra um evento de domínio
     */
    private function recordEvent(string $eventName, array $data): void
    {
        $this->domainEvents[] = [
            'event' => $eventName,
            'data' => $data,
            'occurred_at' => new DateTimeImmutable(),
        ];
    }

    /**
     * Retorna e limpa os eventos de domínio
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // Getters

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getName(): ProductName
    {
        return $this->name;
    }

    public function getSku(): SKU
    {
        return $this->sku;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getCategoryId(): ?CategoryId
    {
        return $this->categoryId;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name->value(),
            'sku' => $this->sku->value(),
            'price' => $this->price->value(),
            'category_id' => $this->categoryId?->value(),
            'barcode' => $this->barcode,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

