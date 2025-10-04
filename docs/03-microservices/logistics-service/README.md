# Logistics Service - Serviço de Gestão de Logística

## Visão Geral

O Logistics Service é responsável por gerenciar todo o processo de logística e entrega de pedidos.

## Bounded Context

**Domínio:** Logística e Entregas

### Responsabilidades

- Gestão de envios
- Rastreamento de pedidos
- Integração com transportadoras
- Cálculo de frete
- Gestão de rotas
- Status de entrega
- Gestão de devoluções

### O que NÃO é responsabilidade

- Gestão de vendas
- Gestão de estoque
- Processamento de pagamentos
- Emissão de notas fiscais

## Modelo de Domínio

### Entidades

#### Shipment (Aggregate Root)

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ShipmentId;
use App\Domain\ValueObjects\OrderId;

class Shipment extends BaseEntity
{
    private ShipmentId $shipmentId;
    private OrderId $orderId;
    private string $status; // pending, preparing, dispatched, in_transit, out_for_delivery, delivered, failed
    private Address $origin;
    private Address $destination;
    private ?string $trackingCode;
    private ?string $carrierId;
    private ?DateTimeImmutable $estimatedDeliveryDate;
    private ?DateTimeImmutable $actualDeliveryDate;
    private array $trackingHistory;
    private ?string $deliveryProof;

    public function __construct(
        ShipmentId $shipmentId,
        OrderId $orderId,
        Address $origin,
        Address $destination
    ) {
        $this->shipmentId = $shipmentId;
        $this->orderId = $orderId;
        $this->status = 'pending';
        $this->origin = $origin;
        $this->destination = $destination;
        $this->trackingHistory = [];
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function prepare(): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Only pending shipments can be prepared');
        }

        $this->status = 'preparing';
        $this->addTrackingEvent('Pedido em preparação');
        $this->updatedAt = new DateTimeImmutable();
    }

    public function dispatch(string $trackingCode, string $carrierId): void
    {
        if ($this->status !== 'preparing') {
            throw new \DomainException('Only preparing shipments can be dispatched');
        }

        $this->status = 'dispatched';
        $this->trackingCode = $trackingCode;
        $this->carrierId = $carrierId;
        $this->addTrackingEvent('Pedido despachado');
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markInTransit(): void
    {
        if ($this->status !== 'dispatched') {
            throw new \DomainException('Only dispatched shipments can be in transit');
        }

        $this->status = 'in_transit';
        $this->addTrackingEvent('Pedido em trânsito');
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markOutForDelivery(): void
    {
        if ($this->status !== 'in_transit') {
            throw new \DomainException('Only in transit shipments can be out for delivery');
        }

        $this->status = 'out_for_delivery';
        $this->addTrackingEvent('Saiu para entrega');
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deliver(string $deliveryProof): void
    {
        if ($this->status !== 'out_for_delivery') {
            throw new \DomainException('Only out for delivery shipments can be delivered');
        }

        $this->status = 'delivered';
        $this->deliveryProof = $deliveryProof;
        $this->actualDeliveryDate = new DateTimeImmutable();
        $this->addTrackingEvent('Pedido entregue');
        $this->updatedAt = new DateTimeImmutable();
    }

    public function fail(string $reason): void
    {
        $this->status = 'failed';
        $this->addTrackingEvent("Falha na entrega: {$reason}");
        $this->updatedAt = new DateTimeImmutable();
    }

    private function addTrackingEvent(string $message): void
    {
        $this->trackingHistory[] = [
            'timestamp' => new DateTimeImmutable(),
            'status' => $this->status,
            'message' => $message,
        ];
    }

    public function setEstimatedDeliveryDate(DateTimeImmutable $date): void
    {
        $this->estimatedDeliveryDate = $date;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Carrier

```php
<?php

namespace App\Domain\Entities;

class Carrier extends BaseEntity
{
    private string $name;
    private string $code;
    private bool $isActive;
    private array $serviceTypes;
    private string $apiEndpoint;
    private ?string $apiKey;

    public function __construct(
        string $name,
        string $code,
        string $apiEndpoint
    ) {
        $this->id = uniqid('carrier_', true);
        $this->name = $name;
        $this->code = strtoupper($code);
        $this->isActive = true;
        $this->serviceTypes = [];
        $this->apiEndpoint = $apiEndpoint;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addServiceType(string $type, array $config): void
    {
        $this->serviceTypes[$type] = $config;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Route

```php
<?php

namespace App\Domain\Entities;

class Route extends BaseEntity
{
    private string $name;
    private array $waypoints;
    private string $carrierId;
    private DateTimeImmutable $departureDate;
    private ?DateTimeImmutable $arrivalDate;
    private string $status; // planned, in_progress, completed, cancelled

    // Constructor and methods...
}
```

### Value Objects

#### Address

```php
<?php

namespace App\Domain\ValueObjects;

class Address
{
    public function __construct(
        private string $street,
        private string $number,
        private ?string $complement,
        private string $neighborhood,
        private string $city,
        private string $state,
        private string $zipCode,
        private string $country = 'BR'
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->street) || empty($this->city)) {
            throw new \InvalidArgumentException('Address must have street and city');
        }
    }

    public function getFullAddress(): string
    {
        $parts = [
            $this->street,
            $this->number,
            $this->complement,
            $this->neighborhood,
            $this->city,
            $this->state,
            $this->zipCode,
        ];

        return implode(', ', array_filter($parts));
    }

    // Getters...
}
```

### Domain Events

```php
<?php

namespace App\Domain\Events;

class ShipmentCreatedEvent extends DomainEvent
{
    public function __construct(
        private string $shipmentId,
        private string $orderId,
        private array $destination
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'logistics.shipment.created';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'shipment_id' => $this->shipmentId,
                'order_id' => $this->orderId,
                'destination' => $this->destination,
            ],
        ];
    }
}

class ShipmentDispatchedEvent extends DomainEvent
{
    public function __construct(
        private string $shipmentId,
        private string $orderId,
        private string $trackingCode,
        private string $estimatedDeliveryDate
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'logistics.shipment.dispatched';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'shipment_id' => $this->shipmentId,
                'order_id' => $this->orderId,
                'tracking_code' => $this->trackingCode,
                'estimated_delivery_date' => $this->estimatedDeliveryDate,
            ],
        ];
    }
}

class ShipmentDeliveredEvent extends DomainEvent
{
    public function __construct(
        private string $shipmentId,
        private string $orderId,
        private string $deliveredAt
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'logistics.shipment.delivered';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'shipment_id' => $this->shipmentId,
                'order_id' => $this->orderId,
                'delivered_at' => $this->deliveredAt,
            ],
        ];
    }
}
```

## Casos de Uso

### 1. Create Shipment

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\Shipment;
use App\Domain\Events\ShipmentCreatedEvent;

class CreateShipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private ShipmentRepositoryInterface $shipmentRepository,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): array
    {
        $shipment = new Shipment(
            new ShipmentId(),
            new OrderId($input['order_id']),
            new Address(...$input['origin']),
            new Address(...$input['destination'])
        );

        $this->shipmentRepository->save($shipment);

        $event = new ShipmentCreatedEvent(
            $shipment->getShipmentId()->value(),
            $shipment->getOrderId()->value(),
            $input['destination']
        );

        $this->eventPublisher->publish($event);

        return [
            'shipment_id' => $shipment->getShipmentId()->value(),
            'status' => $shipment->getStatus(),
        ];
    }
}
```

### 2. Dispatch Shipment

```php
<?php

namespace App\Application\UseCases\Commands;

class DispatchShipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private ShipmentRepositoryInterface $shipmentRepository,
        private CarrierServiceInterface $carrierService,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): void
    {
        $shipment = $this->shipmentRepository->findById(
            new ShipmentId($input['shipment_id'])
        );

        if (!$shipment) {
            throw new \DomainException('Shipment not found');
        }

        // Integrar com transportadora
        $trackingCode = $this->carrierService->createShipment([
            'origin' => $shipment->getOrigin(),
            'destination' => $shipment->getDestination(),
            'carrier_id' => $input['carrier_id'],
        ]);

        // Estimar data de entrega
        $estimatedDate = $this->carrierService->calculateDeliveryDate(
            $input['carrier_id'],
            $shipment->getDestination()
        );

        $shipment->dispatch($trackingCode, $input['carrier_id']);
        $shipment->setEstimatedDeliveryDate($estimatedDate);

        $this->shipmentRepository->save($shipment);

        $event = new ShipmentDispatchedEvent(
            $shipment->getShipmentId()->value(),
            $shipment->getOrderId()->value(),
            $trackingCode,
            $estimatedDate->format('Y-m-d H:i:s')
        );

        $this->eventPublisher->publish($event);
    }
}
```

## API Endpoints

### Shipments
```
GET    /api/v1/shipments                    - Listar envios
POST   /api/v1/shipments                    - Criar envio
GET    /api/v1/shipments/{id}               - Obter envio
PUT    /api/v1/shipments/{id}               - Atualizar envio
POST   /api/v1/shipments/{id}/dispatch      - Despachar envio
POST   /api/v1/shipments/{id}/deliver       - Marcar como entregue
GET    /api/v1/shipments/{id}/tracking      - Rastreamento
GET    /api/v1/shipments/order/{orderId}    - Envio por pedido
GET    /api/v1/shipments/tracking/{code}    - Rastreamento por código
```

### Carriers
```
GET    /api/v1/carriers                 - Listar transportadoras
POST   /api/v1/carriers                 - Criar transportadora
GET    /api/v1/carriers/{id}            - Obter transportadora
PUT    /api/v1/carriers/{id}            - Atualizar transportadora
POST   /api/v1/carriers/calculate-freight - Calcular frete
```

## Schema do Banco de Dados

```sql
-- Shipments Table
CREATE TABLE shipments (
    id UUID PRIMARY KEY,
    order_id UUID NOT NULL,
    status VARCHAR(20) NOT NULL,
    tracking_code VARCHAR(100) UNIQUE,
    carrier_id UUID REFERENCES carriers(id),
    origin_street VARCHAR(255) NOT NULL,
    origin_number VARCHAR(20) NOT NULL,
    origin_complement VARCHAR(100),
    origin_neighborhood VARCHAR(100) NOT NULL,
    origin_city VARCHAR(100) NOT NULL,
    origin_state VARCHAR(2) NOT NULL,
    origin_zip_code VARCHAR(10) NOT NULL,
    destination_street VARCHAR(255) NOT NULL,
    destination_number VARCHAR(20) NOT NULL,
    destination_complement VARCHAR(100),
    destination_neighborhood VARCHAR(100) NOT NULL,
    destination_city VARCHAR(100) NOT NULL,
    destination_state VARCHAR(2) NOT NULL,
    destination_zip_code VARCHAR(10) NOT NULL,
    estimated_delivery_date TIMESTAMP,
    actual_delivery_date TIMESTAMP,
    delivery_proof TEXT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_shipments_order_id ON shipments(order_id);
CREATE INDEX idx_shipments_tracking_code ON shipments(tracking_code);
CREATE INDEX idx_shipments_status ON shipments(status);

-- Tracking History Table
CREATE TABLE tracking_history (
    id UUID PRIMARY KEY,
    shipment_id UUID REFERENCES shipments(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    location VARCHAR(255),
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_tracking_history_shipment_id ON tracking_history(shipment_id);

-- Carriers Table
CREATE TABLE carriers (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    api_endpoint VARCHAR(255),
    api_key VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    service_types JSONB,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_carriers_code ON carriers(code);

-- Routes Table
CREATE TABLE routes (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    carrier_id UUID REFERENCES carriers(id),
    waypoints JSONB NOT NULL,
    departure_date TIMESTAMP NOT NULL,
    arrival_date TIMESTAMP,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_routes_carrier_id ON routes(carrier_id);
CREATE INDEX idx_routes_status ON routes(status);
```

## Eventos Publicados

- logistics.shipment.created
- logistics.shipment.dispatched
- logistics.shipment.in_transit
- logistics.shipment.out_for_delivery
- logistics.shipment.delivered
- logistics.shipment.failed

## Eventos Consumidos

### sales.order.confirmed

```php
public function handle(OrderConfirmedEvent $event)
{
    // Criar shipment automaticamente
    $this->createShipmentUseCase->execute([
        'order_id' => $event->orderId,
        'destination' => $event->shippingAddress,
        'origin' => config('logistics.warehouse_address'),
    ]);
}
```

### financial.payment.processed

```php
public function handle(PaymentProcessedEvent $event)
{
    // Liberar para preparação do envio
    $shipment = $this->shipmentRepository->findByOrderId($event->orderId);
    if ($shipment) {
        $shipment->prepare();
        $this->shipmentRepository->save($shipment);
    }
}
```

## Integrações Externas

### Transportadoras

```php
<?php

namespace App\Infrastructure\External\Carriers;

interface CarrierServiceInterface
{
    public function createShipment(array $data): string;
    public function calculateDeliveryDate(string $carrierId, Address $destination): DateTimeImmutable;
    public function calculateFreight(string $carrierId, Address $origin, Address $destination, float $weight): Money;
    public function trackShipment(string $trackingCode): array;
    public function cancelShipment(string $trackingCode): bool;
}

// Implementações específicas
class CorreiosCarrierService implements CarrierServiceInterface { }
class JadlogCarrierService implements CarrierServiceInterface { }
class TotalExpressCarrierService implements CarrierServiceInterface { }
```

## Resiliência

### Circuit Breaker
- APIs de transportadoras (timeout: 10s)

### Retry
- 3 tentativas para criação de envio
- 5 tentativas para consulta de rastreamento
- Backoff exponencial

### Fallback
- Se API de transportadora falhar, usar estimativa padrão
- Cache de cálculos de frete (TTL: 1 hora)

---

**Próximo:** [Financial Service](../financial-service/README.md)

