<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence;

use App\Models\Order as OrderModel;
use App\Models\OrderItem as OrderItemModel;
use Src\Domain\Entities\Order;
use Src\Domain\Entities\OrderItem;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderNumber;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\OrderStatus;
use Src\Domain\ValueObjects\PaymentStatus;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\OrderItemId;
use Src\Domain\ValueObjects\Quantity;

/**
 * Eloquent Order Repository
 * 
 * Implementação do repositório usando Eloquent ORM.
 */
final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        // Salvar Order
        $orderData = [
            'order_number' => $order->getOrderNumber()->value(),
            'customer_id' => $order->getCustomerId()->value(),
            'status' => $order->getStatus()->value(),
            'subtotal' => $order->getSubtotal()->value(),
            'discount' => $order->getDiscount()->value(),
            'total' => $order->getTotal()->value(),
            'payment_status' => $order->getPaymentStatus()->value(),
            'payment_method' => $order->getPaymentMethod(),
            'notes' => $order->getNotes(),
            'confirmed_at' => $order->getConfirmedAt(),
            'cancelled_at' => $order->getCancelledAt(),
            'delivered_at' => $order->getDeliveredAt(),
            'updated_at' => $order->getUpdatedAt(),
        ];

        $orderModel = OrderModel::updateOrCreate(
            ['id' => $order->getId()->value()],
            $orderData
        );

        // Salvar OrderItems
        // Delete existing items and recreate (simpler approach)
        OrderItemModel::where('order_id', $order->getId()->value())->delete();

        foreach ($order->getItems() as $item) {
            OrderItemModel::create([
                'id' => $item->getId()->value(),
                'order_id' => $order->getId()->value(),
                'product_id' => $item->getProductId(),
                'product_name' => $item->getProductName(),
                'sku' => $item->getSku(),
                'quantity' => $item->getQuantity()->value(),
                'unit_price' => $item->getUnitPrice()->value(),
                'subtotal' => $item->getSubtotal()->value(),
                'discount' => $item->getDiscount()->value(),
                'total' => $item->getTotal()->value(),
            ]);
        }
    }

    public function findById(OrderId $id): ?Order
    {
        $model = OrderModel::with('items')->find($id->value());
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByOrderNumber(OrderNumber $orderNumber): ?Order
    {
        $model = OrderModel::with('items')
            ->where('order_number', $orderNumber->value())
            ->first();
        
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function existsOrderNumber(OrderNumber $orderNumber): bool
    {
        return OrderModel::where('order_number', $orderNumber->value())->exists();
    }

    public function nextOrderNumber(): OrderNumber
    {
        $year = date('Y');
        
        // Get last order number for current year
        $lastOrder = OrderModel::where('order_number', 'LIKE', "ORD-{$year}-%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            // Extract sequence from last order number (ORD-2024-0123)
            preg_match('/ORD-\d{4}-(\d+)$/', $lastOrder->order_number, $matches);
            $sequence = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        } else {
            $sequence = 1;
        }

        return OrderNumber::generate($sequence);
    }

    public function findByCustomerId(CustomerId $customerId, int $page = 1, int $perPage = 15): array
    {
        $models = OrderModel::with('items')
            ->where('customer_id', $customerId->value())
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = OrderModel::with('items');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $models = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $models->map(fn($model) => $this->toDomainEntity($model))->all();
    }

    public function count(array $filters = []): int
    {
        $query = OrderModel::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->count();
    }

    public function delete(OrderId $id): void
    {
        // Cascade delete will handle order_items
        OrderModel::where('id', $id->value())->delete();
    }

    /**
     * Converte Model para Domain Entity
     */
    private function toDomainEntity(OrderModel $model): Order
    {
        // Convert items
        $items = $model->items->map(function ($itemModel) {
            return OrderItem::reconstitute(
                id: OrderItemId::fromString($itemModel->id),
                productId: $itemModel->product_id,
                productName: $itemModel->product_name,
                sku: $itemModel->sku,
                quantity: Quantity::fromInt($itemModel->quantity),
                unitPrice: Money::fromFloat($itemModel->unit_price),
                subtotal: Money::fromFloat($itemModel->subtotal),
                discount: Money::fromFloat($itemModel->discount),
                total: Money::fromFloat($itemModel->total),
                createdAt: \DateTimeImmutable::createFromMutable($itemModel->created_at),
                updatedAt: $itemModel->updated_at ? \DateTimeImmutable::createFromMutable($itemModel->updated_at) : null
            );
        })->all();

        return Order::reconstitute(
            id: OrderId::fromString($model->id),
            orderNumber: OrderNumber::fromString($model->order_number),
            customerId: CustomerId::fromString($model->customer_id),
            status: OrderStatus::fromString($model->status),
            subtotal: Money::fromFloat($model->subtotal),
            discount: Money::fromFloat($model->discount),
            total: Money::fromFloat($model->total),
            paymentStatus: PaymentStatus::fromString($model->payment_status),
            paymentMethod: $model->payment_method,
            notes: $model->notes,
            confirmedAt: $model->confirmed_at ? \DateTimeImmutable::createFromMutable($model->confirmed_at) : null,
            cancelledAt: $model->cancelled_at ? \DateTimeImmutable::createFromMutable($model->cancelled_at) : null,
            deliveredAt: $model->delivered_at ? \DateTimeImmutable::createFromMutable($model->delivered_at) : null,
            createdAt: \DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: $model->updated_at ? \DateTimeImmutable::createFromMutable($model->updated_at) : null,
            items: $items
        );
    }
}
