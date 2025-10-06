<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\OrderItemId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\Quantity;

/**
 * Order Item Entity
 * 
 * Representa um item de pedido (produto + quantidade + preço).
 * Esta entidade é parte do aggregate Order.
 */
final class OrderItem
{
    private function __construct(
        private readonly OrderItemId $id,
        private readonly string $productId,  // UUID do produto no Inventory Service
        private readonly string $productName, // Snapshot do nome no momento da venda
        private readonly string $sku,         // Snapshot do SKU no momento da venda
        private readonly Quantity $quantity,
        private readonly Money $unitPrice,    // Preço unitário no momento da venda
        private readonly Money $subtotal,     // quantity * unitPrice
        private Money $discount,
        private Money $total,                 // subtotal - discount
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = null
    ) {
    }

    /**
     * Cria um novo item de pedido
     */
    public static function create(
        OrderItemId $id,
        string $productId,
        string $productName,
        string $sku,
        Quantity $quantity,
        Money $unitPrice
    ): self {
        $subtotal = $unitPrice->multiply((float) $quantity->value());
        $discount = Money::zero();
        $total = $subtotal;

        return new self(
            id: $id,
            productId: $productId,
            productName: $productName,
            sku: $sku,
            quantity: $quantity,
            unitPrice: $unitPrice,
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
            createdAt: new DateTimeImmutable()
        );
    }

    /**
     * Reconstitui um item do banco de dados
     */
    public static function reconstitute(
        OrderItemId $id,
        string $productId,
        string $productName,
        string $sku,
        Quantity $quantity,
        Money $unitPrice,
        Money $subtotal,
        Money $discount,
        Money $total,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            productName: $productName,
            sku: $sku,
            quantity: $quantity,
            unitPrice: $unitPrice,
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Aplica desconto ao item
     */
    public function applyDiscount(Money $discountAmount): void
    {
        if ($discountAmount->greaterThan($this->subtotal)) {
            throw new \DomainException('Discount cannot be greater than subtotal');
        }

        $this->discount = $discountAmount;
        $this->total = $this->subtotal->subtract($discountAmount);
        $this->touch();
    }

    /**
     * Remove desconto
     */
    public function removeDiscount(): void
    {
        $this->discount = Money::zero();
        $this->total = $this->subtotal;
        $this->touch();
    }

    /**
     * Verifica se tem desconto
     */
    public function hasDiscount(): bool
    {
        return $this->discount->isPositive();
    }

    /**
     * Getters
     */
    public function getId(): OrderItemId
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getSubtotal(): Money
    {
        return $this->subtotal;
    }

    public function getDiscount(): Money
    {
        return $this->discount;
    }

    public function getTotal(): Money
    {
        return $this->total;
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
     * Atualiza a data de modificação
     */
    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
