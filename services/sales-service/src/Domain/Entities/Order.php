<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderNumber;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\OrderStatus;
use Src\Domain\ValueObjects\PaymentStatus;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\OrderItemId;

/**
 * Order Entity (Aggregate Root)
 *
 * Representa um pedido de venda completo.
 */
final class Order
{
    private array $domainEvents = [];

    /** @var OrderItem[] */
    private array $items = [];

    private function __construct(
        private readonly OrderId $id,
        private readonly OrderNumber $orderNumber,
        private readonly CustomerId $customerId,
        private OrderStatus $status,
        private Money $subtotal,
        private Money $discount,
        private Money $total,
        private PaymentStatus $paymentStatus,
        private ?string $paymentMethod = null,
        private ?string $notes = null,
        private ?DateTimeImmutable $confirmedAt = null,
        private ?DateTimeImmutable $cancelledAt = null,
        private ?DateTimeImmutable $deliveredAt = null,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = null
    ) {
    }

    /**
     * Cria um pedido (draft)
     */
    public static function create(
        OrderId $id,
        OrderNumber $orderNumber,
        CustomerId $customerId,
        ?string $notes = null
    ): self {
        $order = new self(
            id: $id,
            orderNumber: $orderNumber,
            customerId: $customerId,
            status: OrderStatus::draft(),
            subtotal: Money::zero(),
            discount: Money::zero(),
            total: Money::zero(),
            paymentStatus: PaymentStatus::pending(),
            notes: $notes,
            createdAt: new DateTimeImmutable()
        );

        $order->recordEvent('OrderCreated', [
            'order_id' => $id->value(),
            'order_number' => $orderNumber->value(),
            'customer_id' => $customerId->value(),
        ]);

        return $order;
    }

    /**
     * Reconstitui um pedido do banco de dados
     */
    public static function reconstitute(
        OrderId $id,
        OrderNumber $orderNumber,
        CustomerId $customerId,
        OrderStatus $status,
        Money $subtotal,
        Money $discount,
        Money $total,
        PaymentStatus $paymentStatus,
        ?string $paymentMethod,
        ?string $notes,
        ?DateTimeImmutable $confirmedAt,
        ?DateTimeImmutable $cancelledAt,
        ?DateTimeImmutable $deliveredAt,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt,
        array $items = []
    ): self {
        $order = new self(
            id: $id,
            orderNumber: $orderNumber,
            customerId: $customerId,
            status: $status,
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
            paymentStatus: $paymentStatus,
            paymentMethod: $paymentMethod,
            notes: $notes,
            confirmedAt: $confirmedAt,
            cancelledAt: $cancelledAt,
            deliveredAt: $deliveredAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        $order->items = $items;

        return $order;
    }

    /**
     * Adiciona item ao pedido
     */
    public function addItem(OrderItem $item): void
    {
        if (!$this->status->isDraft()) {
            throw new \DomainException('Cannot add items to a non-draft order');
        }

        $this->items[] = $item;
        $this->recalculateTotals();
        $this->touch();
    }

    /**
     * Remove item do pedido
     */
    public function removeItem(OrderItemId $itemId): void
    {
        if (!$this->status->isDraft()) {
            throw new \DomainException('Cannot remove items from a non-draft order');
        }

        $this->items = array_filter(
            $this->items,
            fn(OrderItem $item) => !$item->getId()->equals($itemId)
        );

        $this->items = array_values($this->items); // Reindex array

        $this->recalculateTotals();
        $this->touch();
    }

    /**
     * Aplica desconto ao pedido
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
     * Confirma o pedido
     */
    public function confirm(): void
    {
        if (!$this->status->isDraft() && !$this->status->isPending()) {
            throw new \DomainException('Only draft or pending orders can be confirmed');
        }

        if ($this->isEmpty()) {
            throw new \DomainException('Cannot confirm an order without items');
        }

        $this->status = OrderStatus::confirmed();
        $this->confirmedAt = new DateTimeImmutable();
        $this->touch();

        $this->recordEvent('OrderConfirmed', [
            'order_id' => $this->id->value(),
            'order_number' => $this->orderNumber->value(),
            'customer_id' => $this->customerId->value(),
            'total' => $this->total->value(),
            'items' => array_map(fn($item) => [
                'product_id' => $item->getProductId(),
                'quantity' => $item->getQuantity()->value(),
            ], $this->items),
        ]);
    }

    /**
     * Cancela o pedido
     */
    public function cancel(string $reason = null): void
    {
        if (!$this->status->canBeCancelled()) {
            throw new \DomainException("Order with status {$this->status->value()} cannot be cancelled");
        }

        $this->status = OrderStatus::cancelled();
        $this->cancelledAt = new DateTimeImmutable();
        $this->touch();

        $this->recordEvent('OrderCancelled', [
            'order_id' => $this->id->value(),
            'order_number' => $this->orderNumber->value(),
            'reason' => $reason,
            'items' => array_map(fn($item) => [
                'product_id' => $item->getProductId(),
                'quantity' => $item->getQuantity()->value(),
            ], $this->items),
        ]);
    }

    /**
     * Marca o pedido como entregue
     */
    public function deliver(): void
    {
        if (!$this->status->isConfirmed()) {
            throw new \DomainException('Only confirmed orders can be delivered');
        }

        $this->status = OrderStatus::delivered();
        $this->deliveredAt = new DateTimeImmutable();
        $this->touch();

        $this->recordEvent('OrderDelivered', [
            'order_id' => $this->id->value(),
            'order_number' => $this->orderNumber->value(),
        ]);
    }

    /**
     * Atualiza status do pedido
     */
    public function updateStatus(OrderStatus $newStatus): void
    {
        if ($this->status->equals($newStatus)) {
            return;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->touch();

        // Registra data de entrega
        if ($newStatus->isDelivered()) {
            $this->deliveredAt = new DateTimeImmutable();

            $this->recordEvent('OrderDelivered', [
                'order_id' => $this->id->value(),
                'order_number' => $this->orderNumber->value(),
            ]);
        }

        $this->recordEvent('OrderStatusUpdated', [
            'order_id' => $this->id->value(),
            'old_status' => $oldStatus->value(),
            'new_status' => $newStatus->value(),
        ]);
    }

    /**
     * Atualiza status de pagamento
     */
    public function updatePaymentStatus(PaymentStatus $newStatus, ?string $paymentMethod = null): void
    {
        $this->paymentStatus = $newStatus;

        if ($paymentMethod !== null) {
            $this->paymentMethod = $paymentMethod;
        }

        $this->touch();

        if ($newStatus->isPaid()) {
            $this->recordEvent('PaymentReceived', [
                'order_id' => $this->id->value(),
                'order_number' => $this->orderNumber->value(),
                'amount' => $this->total->value(),
                'payment_method' => $this->paymentMethod,
            ]);
        }
    }

    /**
     * Verifica se o pedido está vazio
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Retorna quantidade total de itens
     */
    public function getItemsCount(): int
    {
        return count($this->items);
    }

    /**
     * Recalcula os totais do pedido
     */
    private function recalculateTotals(): void
    {
        $subtotal = Money::zero();
        $discount = Money::zero();

        foreach ($this->items as $item) {
            $subtotal = $subtotal->add($item->getSubtotal());
            $discount = $discount->add($item->getDiscount());
        }

        $this->subtotal = $subtotal;
        $this->discount = $discount;
        $this->total = $subtotal->subtract($discount);
    }

    /**
     * Getters
     */
    public function getId(): OrderId
    {
        return $this->id;
    }

    public function getOrderNumber(): OrderNumber
    {
        return $this->orderNumber;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
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

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
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
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items;
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
