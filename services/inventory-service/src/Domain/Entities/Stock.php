<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\StockId;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;
use Src\Domain\Exceptions\InsufficientStockException;

/**
 * Stock Entity (Aggregate Root)
 * 
 * Representa o controle de estoque de um produto.
 */
final class Stock
{
    private array $domainEvents = [];
    private array $movements = [];

    private function __construct(
        private readonly StockId $id,
        private readonly ProductId $productId,
        private Quantity $quantity,
        private Quantity $minimumQuantity,
        private ?Quantity $maximumQuantity = null,
        private ?DateTimeImmutable $lastMovementAt = null,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = null
    ) {
    }

    /**
     * Cria um novo controle de estoque
     */
    public static function create(
        StockId $id,
        ProductId $productId,
        Quantity $initialQuantity,
        Quantity $minimumQuantity,
        ?Quantity $maximumQuantity = null
    ): self {
        $stock = new self(
            id: $id,
            productId: $productId,
            quantity: $initialQuantity,
            minimumQuantity: $minimumQuantity,
            maximumQuantity: $maximumQuantity,
            lastMovementAt: new DateTimeImmutable(),
            createdAt: new DateTimeImmutable()
        );

        $stock->recordEvent('StockCreated', [
            'stock_id' => $id->value(),
            'product_id' => $productId->value(),
            'initial_quantity' => $initialQuantity->value(),
            'minimum_quantity' => $minimumQuantity->value(),
        ]);

        return $stock;
    }

    /**
     * Reconstitui um stock do banco de dados
     */
    public static function reconstitute(
        StockId $id,
        ProductId $productId,
        Quantity $quantity,
        Quantity $minimumQuantity,
        ?Quantity $maximumQuantity,
        ?DateTimeImmutable $lastMovementAt,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            quantity: $quantity,
            minimumQuantity: $minimumQuantity,
            maximumQuantity: $maximumQuantity,
            lastMovementAt: $lastMovementAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Aumenta o estoque (entrada)
     */
    public function increase(Quantity $amount, string $reason, ?string $referenceId = null): void
    {
        $previousQuantity = $this->quantity;
        $this->quantity = $this->quantity->add($amount);
        $this->lastMovementAt = new DateTimeImmutable();
        $this->touch();

        $this->recordMovement('IN', $amount, $previousQuantity, $this->quantity, $reason, $referenceId);

        $this->recordEvent('StockIncreased', [
            'stock_id' => $this->id->value(),
            'product_id' => $this->productId->value(),
            'amount' => $amount->value(),
            'previous_quantity' => $previousQuantity->value(),
            'new_quantity' => $this->quantity->value(),
            'reason' => $reason,
        ]);
    }

    /**
     * Diminui o estoque (saída)
     */
    public function decrease(Quantity $amount, string $reason, ?string $referenceId = null): void
    {
        if (!$this->quantity->isSufficient($amount)) {
            throw new InsufficientStockException(
                "Insufficient stock. Available: {$this->quantity->value()}, Required: {$amount->value()}"
            );
        }

        $previousQuantity = $this->quantity;
        $this->quantity = $this->quantity->subtract($amount);
        $this->lastMovementAt = new DateTimeImmutable();
        $this->touch();

        $this->recordMovement('OUT', $amount, $previousQuantity, $this->quantity, $reason, $referenceId);

        $this->recordEvent('StockDecreased', [
            'stock_id' => $this->id->value(),
            'product_id' => $this->productId->value(),
            'amount' => $amount->value(),
            'previous_quantity' => $previousQuantity->value(),
            'new_quantity' => $this->quantity->value(),
            'reason' => $reason,
        ]);

        // Verifica se está abaixo do estoque mínimo
        if ($this->isLowStock()) {
            $this->recordEvent('StockLowAlert', [
                'stock_id' => $this->id->value(),
                'product_id' => $this->productId->value(),
                'current_quantity' => $this->quantity->value(),
                'minimum_quantity' => $this->minimumQuantity->value(),
            ]);
        }

        // Verifica se está zerado
        if ($this->quantity->isZero()) {
            $this->recordEvent('StockDepleted', [
                'stock_id' => $this->id->value(),
                'product_id' => $this->productId->value(),
            ]);
        }
    }

    /**
     * Ajusta o estoque (inventário)
     */
    public function adjust(Quantity $newQuantity, string $reason): void
    {
        $previousQuantity = $this->quantity;
        
        if ($previousQuantity->equals($newQuantity)) {
            return;
        }

        $this->quantity = $newQuantity;
        $this->lastMovementAt = new DateTimeImmutable();
        $this->touch();

        // Calcula a diferença para registrar a movimentação
        if ($newQuantity->greaterThan($previousQuantity)) {
            $difference = Quantity::fromInt($newQuantity->value() - $previousQuantity->value());
            $this->recordMovement('ADJUSTMENT', $difference, $previousQuantity, $newQuantity, $reason);
        } else {
            $difference = Quantity::fromInt($previousQuantity->value() - $newQuantity->value());
            $this->recordMovement('ADJUSTMENT', $difference, $previousQuantity, $newQuantity, $reason);
        }

        $this->recordEvent('StockAdjusted', [
            'stock_id' => $this->id->value(),
            'product_id' => $this->productId->value(),
            'previous_quantity' => $previousQuantity->value(),
            'new_quantity' => $newQuantity->value(),
            'reason' => $reason,
        ]);

        // Verifica alertas
        if ($this->isLowStock()) {
            $this->recordEvent('StockLowAlert', [
                'stock_id' => $this->id->value(),
                'product_id' => $this->productId->value(),
                'current_quantity' => $this->quantity->value(),
                'minimum_quantity' => $this->minimumQuantity->value(),
            ]);
        }
    }

    /**
     * Atualiza a quantidade mínima
     */
    public function updateMinimumQuantity(Quantity $minimumQuantity): void
    {
        $this->minimumQuantity = $minimumQuantity;
        $this->touch();
    }

    /**
     * Atualiza a quantidade máxima
     */
    public function updateMaximumQuantity(?Quantity $maximumQuantity): void
    {
        $this->maximumQuantity = $maximumQuantity;
        $this->touch();
    }

    /**
     * Verifica se o estoque está baixo
     */
    public function isLowStock(): bool
    {
        return $this->quantity->lessThanOrEqual($this->minimumQuantity);
    }

    /**
     * Verifica se o estoque está zerado
     */
    public function isDepleted(): bool
    {
        return $this->quantity->isZero();
    }

    /**
     * Verifica se tem quantidade disponível
     */
    public function hasAvailable(Quantity $required): bool
    {
        return $this->quantity->isSufficient($required);
    }

    /**
     * Registra uma movimentação
     */
    private function recordMovement(
        string $type,
        Quantity $amount,
        Quantity $quantityBefore,
        Quantity $quantityAfter,
        string $reason,
        ?string $referenceId = null
    ): void {
        $this->movements[] = [
            'type' => $type,
            'quantity' => $amount->value(),
            'quantity_before' => $quantityBefore->value(),
            'quantity_after' => $quantityAfter->value(),
            'reason' => $reason,
            'reference_id' => $referenceId,
            'occurred_at' => new DateTimeImmutable(),
        ];
    }

    /**
     * Retorna as movimentações pendentes
     */
    public function pullMovements(): array
    {
        $movements = $this->movements;
        $this->movements = [];
        return $movements;
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

    public function getId(): StockId
    {
        return $this->id;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getMinimumQuantity(): Quantity
    {
        return $this->minimumQuantity;
    }

    public function getMaximumQuantity(): ?Quantity
    {
        return $this->maximumQuantity;
    }

    public function getLastMovementAt(): ?DateTimeImmutable
    {
        return $this->lastMovementAt;
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
            'product_id' => $this->productId->value(),
            'quantity' => $this->quantity->value(),
            'minimum_quantity' => $this->minimumQuantity->value(),
            'maximum_quantity' => $this->maximumQuantity?->value(),
            'last_movement_at' => $this->lastMovementAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

