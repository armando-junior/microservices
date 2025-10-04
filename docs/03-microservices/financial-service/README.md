# Financial Service - Serviço de Gestão Financeira

## Visão Geral

O Financial Service é responsável por gerenciar todo o processo financeiro, incluindo pagamentos, contas a receber, contas a pagar e emissão de notas fiscais.

## Bounded Context

**Domínio:** Gestão Financeira

### Responsabilidades

- Processamento de pagamentos
- Contas a receber e pagar
- Emissão de notas fiscais
- Conciliação bancária
- Relatórios financeiros
- Integração com gateways de pagamento
- Gestão de transações

### O que NÃO é responsabilidade

- Gestão de vendas
- Gestão de estoque
- Logística

## Modelo de Domínio

### Entidades

#### Payment (Aggregate Root)

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\PaymentId;
use App\Domain\ValueObjects\OrderId;
use App\Domain\ValueObjects\Money;

class Payment extends BaseEntity
{
    private PaymentId $paymentId;
    private OrderId $orderId;
    private Money $amount;
    private string $method; // credit_card, debit_card, pix, boleto, bank_transfer
    private string $status; // pending, processing, approved, declined, refunded, cancelled
    private ?string $gatewayTransactionId;
    private ?string $gatewayResponse;
    private array $metadata;
    private ?DateTimeImmutable $processedAt;
    private ?DateTimeImmutable $approvedAt;

    public function __construct(
        PaymentId $paymentId,
        OrderId $orderId,
        Money $amount,
        string $method,
        array $metadata = []
    ) {
        $this->paymentId = $paymentId;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->method = $method;
        $this->status = 'pending';
        $this->metadata = $metadata;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function process(string $gatewayTransactionId): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Only pending payments can be processed');
        }

        $this->status = 'processing';
        $this->gatewayTransactionId = $gatewayTransactionId;
        $this->processedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function approve(): void
    {
        if ($this->status !== 'processing') {
            throw new \DomainException('Only processing payments can be approved');
        }

        $this->status = 'approved';
        $this->approvedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function decline(string $reason): void
    {
        if (!in_array($this->status, ['pending', 'processing'])) {
            throw new \DomainException('Cannot decline payment in current status');
        }

        $this->status = 'declined';
        $this->gatewayResponse = $reason;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function refund(): void
    {
        if ($this->status !== 'approved') {
            throw new \DomainException('Only approved payments can be refunded');
        }

        $this->status = 'refunded';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (!in_array($this->status, ['pending', 'processing'])) {
            throw new \DomainException('Cannot cancel payment in current status');
        }

        $this->status = 'cancelled';
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Invoice

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\InvoiceId;
use App\Domain\ValueObjects\OrderId;
use App\Domain\ValueObjects\Money;

class Invoice extends BaseEntity
{
    private InvoiceId $invoiceId;
    private OrderId $orderId;
    private string $invoiceNumber;
    private string $series;
    private Money $amount;
    private Money $taxes;
    private Money $total;
    private string $status; // pending, issued, sent, cancelled
    private ?string $nfeKey; // Chave da NF-e
    private ?string $xmlPath;
    private ?string $pdfPath;
    private DateTimeImmutable $issueDate;

    public function __construct(
        InvoiceId $invoiceId,
        OrderId $orderId,
        string $invoiceNumber,
        string $series,
        Money $amount,
        Money $taxes
    ) {
        $this->invoiceId = $invoiceId;
        $this->orderId = $orderId;
        $this->invoiceNumber = $invoiceNumber;
        $this->series = $series;
        $this->amount = $amount;
        $this->taxes = $taxes;
        $this->total = $amount->add($taxes);
        $this->status = 'pending';
        $this->issueDate = new DateTimeImmutable();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function issue(string $nfeKey, string $xmlPath, string $pdfPath): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Only pending invoices can be issued');
        }

        $this->status = 'issued';
        $this->nfeKey = $nfeKey;
        $this->xmlPath = $xmlPath;
        $this->pdfPath = $pdfPath;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsSent(): void
    {
        if ($this->status !== 'issued') {
            throw new \DomainException('Only issued invoices can be sent');
        }

        $this->status = 'sent';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(string $reason): void
    {
        $this->status = 'cancelled';
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Transaction

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Money;

class Transaction extends BaseEntity
{
    private string $type; // debit, credit
    private Money $amount;
    private string $description;
    private string $category; // sales, purchase, refund, fee, etc
    private ?string $referenceType;
    private ?string $referenceId;
    private DateTimeImmutable $transactionDate;

    public function __construct(
        string $type,
        Money $amount,
        string $description,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null
    ) {
        $this->id = uniqid('txn_', true);
        $this->type = $type;
        $this->amount = $amount;
        $this->description = $description;
        $this->category = $category;
        $this->referenceType = $referenceType;
        $this->referenceId = $referenceId;
        $this->transactionDate = new DateTimeImmutable();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

### Domain Events

```php
<?php

namespace App\Domain\Events;

class PaymentProcessedEvent extends DomainEvent
{
    public function __construct(
        private string $paymentId,
        private string $orderId,
        private float $amount,
        private string $status
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'financial.payment.processed';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'payment_id' => $this->paymentId,
                'order_id' => $this->orderId,
                'amount' => $this->amount,
                'status' => $this->status,
            ],
        ];
    }
}

class PaymentFailedEvent extends DomainEvent
{
    public function __construct(
        private string $paymentId,
        private string $orderId,
        private string $reason
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'financial.payment.failed';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'payment_id' => $this->paymentId,
                'order_id' => $this->orderId,
                'reason' => $this->reason,
            ],
        ];
    }
}

class InvoiceGeneratedEvent extends DomainEvent
{
    public function __construct(
        private string $invoiceId,
        private string $orderId,
        private string $invoiceNumber,
        private string $nfeKey
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'financial.invoice.generated';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'invoice_id' => $this->invoiceId,
                'order_id' => $this->orderId,
                'invoice_number' => $this->invoiceNumber,
                'nfe_key' => $this->nfeKey,
            ],
        ];
    }
}
```

## Casos de Uso

### 1. Process Payment

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\Payment;
use App\Domain\Entities\Transaction;
use App\Domain\Events\PaymentProcessedEvent;
use App\Domain\Events\PaymentFailedEvent;

class ProcessPaymentUseCase implements UseCaseInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private PaymentGatewayInterface $paymentGateway,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): array
    {
        // Criar pagamento
        $payment = new Payment(
            new PaymentId(),
            new OrderId($input['order_id']),
            new Money($input['amount']),
            $input['method'],
            $input['metadata'] ?? []
        );

        $this->paymentRepository->save($payment);

        try {
            // Processar no gateway
            $gatewayResponse = $this->paymentGateway->process([
                'amount' => $payment->getAmount()->getAmount(),
                'method' => $payment->getMethod(),
                'metadata' => $payment->getMetadata(),
            ]);

            $payment->process($gatewayResponse['transaction_id']);

            if ($gatewayResponse['status'] === 'approved') {
                $payment->approve();

                // Criar transação de crédito
                $transaction = new Transaction(
                    'credit',
                    $payment->getAmount(),
                    "Payment for order {$input['order_id']}",
                    'sales',
                    'payment',
                    $payment->getPaymentId()->value()
                );

                $this->transactionRepository->save($transaction);

                // Publicar evento de sucesso
                $event = new PaymentProcessedEvent(
                    $payment->getPaymentId()->value(),
                    $input['order_id'],
                    $payment->getAmount()->getAmount(),
                    'approved'
                );
            } else {
                $payment->decline($gatewayResponse['reason']);

                // Publicar evento de falha
                $event = new PaymentFailedEvent(
                    $payment->getPaymentId()->value(),
                    $input['order_id'],
                    $gatewayResponse['reason']
                );
            }

            $this->paymentRepository->save($payment);
            $this->eventPublisher->publish($event);

            return [
                'payment_id' => $payment->getPaymentId()->value(),
                'status' => $payment->getStatus(),
                'transaction_id' => $gatewayResponse['transaction_id'] ?? null,
            ];

        } catch (\Exception $e) {
            $payment->decline($e->getMessage());
            $this->paymentRepository->save($payment);

            $event = new PaymentFailedEvent(
                $payment->getPaymentId()->value(),
                $input['order_id'],
                $e->getMessage()
            );

            $this->eventPublisher->publish($event);

            throw $e;
        }
    }
}
```

### 2. Generate Invoice

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\Invoice;
use App\Domain\Events\InvoiceGeneratedEvent;

class GenerateInvoiceUseCase implements UseCaseInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private NFeServiceInterface $nfeService,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): array
    {
        // Gerar número da nota
        $invoiceNumber = $this->invoiceRepository->getNextInvoiceNumber();

        $invoice = new Invoice(
            new InvoiceId(),
            new OrderId($input['order_id']),
            $invoiceNumber,
            $input['series'] ?? '1',
            new Money($input['amount']),
            new Money($input['taxes'])
        );

        // Emitir NF-e
        $nfeResponse = $this->nfeService->issue([
            'invoice_number' => $invoiceNumber,
            'order_data' => $input['order_data'],
            'customer_data' => $input['customer_data'],
            'items' => $input['items'],
        ]);

        $invoice->issue(
            $nfeResponse['nfe_key'],
            $nfeResponse['xml_path'],
            $nfeResponse['pdf_path']
        );

        $this->invoiceRepository->save($invoice);

        // Publicar evento
        $event = new InvoiceGeneratedEvent(
            $invoice->getInvoiceId()->value(),
            $input['order_id'],
            $invoiceNumber,
            $nfeResponse['nfe_key']
        );

        $this->eventPublisher->publish($event);

        return [
            'invoice_id' => $invoice->getInvoiceId()->value(),
            'invoice_number' => $invoiceNumber,
            'nfe_key' => $nfeResponse['nfe_key'],
            'pdf_url' => $nfeResponse['pdf_path'],
        ];
    }
}
```

## API Endpoints

### Payments
```
GET    /api/v1/payments              - Listar pagamentos
POST   /api/v1/payments              - Processar pagamento
GET    /api/v1/payments/{id}         - Obter pagamento
POST   /api/v1/payments/{id}/refund  - Estornar pagamento
POST   /api/v1/payments/{id}/cancel  - Cancelar pagamento
GET    /api/v1/payments/order/{id}   - Pagamentos de um pedido
```

### Invoices
```
GET    /api/v1/invoices              - Listar notas fiscais
POST   /api/v1/invoices              - Gerar nota fiscal
GET    /api/v1/invoices/{id}         - Obter nota fiscal
POST   /api/v1/invoices/{id}/cancel  - Cancelar nota fiscal
GET    /api/v1/invoices/{id}/pdf     - Download PDF
GET    /api/v1/invoices/{id}/xml     - Download XML
```

### Transactions
```
GET    /api/v1/transactions                - Listar transações
GET    /api/v1/transactions/{id}           - Obter transação
GET    /api/v1/transactions/summary        - Resumo financeiro
GET    /api/v1/transactions/report         - Relatório financeiro
```

## Schema do Banco de Dados

```sql
-- Payments Table
CREATE TABLE payments (
    id UUID PRIMARY KEY,
    order_id UUID NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    method VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    gateway_transaction_id VARCHAR(255),
    gateway_response TEXT,
    metadata JSONB,
    processed_at TIMESTAMP,
    approved_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_payments_order_id ON payments(order_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_gateway_transaction_id ON payments(gateway_transaction_id);

-- Invoices Table
CREATE TABLE invoices (
    id UUID PRIMARY KEY,
    order_id UUID NOT NULL,
    invoice_number VARCHAR(20) NOT NULL,
    series VARCHAR(5) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    taxes DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL,
    nfe_key VARCHAR(44),
    xml_path VARCHAR(255),
    pdf_path VARCHAR(255),
    issue_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    UNIQUE(invoice_number, series)
);

CREATE INDEX idx_invoices_order_id ON invoices(order_id);
CREATE INDEX idx_invoices_nfe_key ON invoices(nfe_key);
CREATE INDEX idx_invoices_status ON invoices(status);

-- Transactions Table
CREATE TABLE transactions (
    id UUID PRIMARY KEY,
    type VARCHAR(10) NOT NULL, -- debit, credit
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    reference_type VARCHAR(50),
    reference_id VARCHAR(255),
    transaction_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_category ON transactions(category);
CREATE INDEX idx_transactions_reference ON transactions(reference_type, reference_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
```

## Eventos Publicados

- financial.payment.requested
- financial.payment.processed
- financial.payment.approved
- financial.payment.failed
- financial.payment.refunded
- financial.invoice.generated
- financial.invoice.cancelled

## Eventos Consumidos

### sales.order.created

```php
public function handle(OrderCreatedEvent $event)
{
    // Criar cobrança e processar pagamento
    $this->processPaymentUseCase->execute([
        'order_id' => $event->orderId,
        'amount' => $event->total,
        'method' => $event->paymentMethod,
        'metadata' => $event->paymentData,
    ]);
}
```

### logistics.shipment.dispatched

```php
public function handle(ShipmentDispatchedEvent $event)
{
    // Gerar nota fiscal quando envio é despachado
    $this->generateInvoiceUseCase->execute([
        'order_id' => $event->orderId,
        'order_data' => $this->getOrderData($event->orderId),
        'customer_data' => $this->getCustomerData($event->orderId),
        'items' => $this->getOrderItems($event->orderId),
    ]);
}
```

## Integrações Externas

### Payment Gateways

```php
<?php

namespace App\Infrastructure\External\Gateways;

interface PaymentGatewayInterface
{
    public function process(array $data): array;
    public function refund(string $transactionId, float $amount): array;
    public function cancel(string $transactionId): array;
    public function getStatus(string $transactionId): string;
}

// Implementações
class StripeGateway implements PaymentGatewayInterface { }
class MercadoPagoGateway implements PaymentGatewayInterface { }
class PagarMeGateway implements PaymentGatewayInterface { }
```

### NF-e Service

```php
<?php

namespace App\Infrastructure\External\Fiscal;

interface NFeServiceInterface
{
    public function issue(array $data): array;
    public function cancel(string $nfeKey, string $reason): array;
    public function query(string $nfeKey): array;
}
```

## Resiliência

### Circuit Breaker
- Gateway de pagamento (timeout: 30s)
- Serviço de NF-e (timeout: 60s)

### Retry
- 3 tentativas para pagamento
- 5 tentativas para emissão de NF-e
- Backoff exponencial

### Idempotência
- Chave de idempotência em pagamentos
- Prevenir processamento duplicado

---

**Próximo:** [Notification Service](../notification-service/README.md)

