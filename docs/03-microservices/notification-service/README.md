# Notification Service - Serviço de Notificações

## Visão Geral

O Notification Service é responsável por gerenciar e enviar todas as notificações do sistema (email, SMS, push notifications).

## Bounded Context

**Domínio:** Notificações e Comunicação

### Responsabilidades

- Envio de emails
- Envio de SMS
- Push notifications
- Gestão de templates
- Histórico de notificações
- Preferências de notificação
- Retry de falhas

### O que NÃO é responsabilidade

- Lógica de negócio
- Decisões sobre quando notificar
- Armazenamento de dados de negócio

## Modelo de Domínio

### Entidades

#### Notification

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\NotificationId;

class Notification extends BaseEntity
{
    private NotificationId $notificationId;
    private string $type; // email, sms, push
    private string $recipient;
    private string $subject;
    private string $content;
    private string $template;
    private array $data;
    private string $status; // pending, sent, failed, retrying
    private int $retryCount;
    private ?string $errorMessage;
    private ?DateTimeImmutable $sentAt;

    public function __construct(
        NotificationId $notificationId,
        string $type,
        string $recipient,
        string $subject,
        string $template,
        array $data = []
    ) {
        $this->notificationId = $notificationId;
        $this->type = $type;
        $this->recipient = $recipient;
        $this->subject = $subject;
        $this->template = $template;
        $this->data = $data;
        $this->status = 'pending';
        $this->retryCount = 0;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sentAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->errorMessage = $errorMessage;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function retry(): void
    {
        if ($this->retryCount >= 3) {
            throw new \DomainException('Max retry attempts reached');
        }

        $this->status = 'retrying';
        $this->retryCount++;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### NotificationTemplate

```php
<?php

namespace App\Domain\Entities;

class NotificationTemplate extends BaseEntity
{
    private string $name;
    private string $type; // email, sms, push
    private string $subject;
    private string $content;
    private array $variables;
    private bool $isActive;

    public function __construct(
        string $name,
        string $type,
        string $subject,
        string $content,
        array $variables = []
    ) {
        $this->id = uniqid('tpl_', true);
        $this->name = $name;
        $this->type = $type;
        $this->subject = $subject;
        $this->content = $content;
        $this->variables = $variables;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function render(array $data): string
    {
        $content = $this->content;
        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        return $content;
    }

    // Getters...
}
```

## Casos de Uso

### 1. Send Notification

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\Notification;
use App\Domain\ValueObjects\NotificationId;

class SendNotificationUseCase implements UseCaseInterface
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private NotificationSenderInterface $notificationSender,
        private TemplateRepositoryInterface $templateRepository
    ) {}

    public function execute($input): array
    {
        // Buscar template
        $template = $this->templateRepository->findByName($input['template']);

        if (!$template) {
            throw new \DomainException('Template not found');
        }

        // Criar notificação
        $notification = new Notification(
            new NotificationId(),
            $input['type'],
            $input['recipient'],
            $template->render($input['data']), // subject
            $input['template'],
            $input['data']
        );

        // Renderizar conteúdo
        $content = $template->render($input['data']);

        $this->notificationRepository->save($notification);

        try {
            // Enviar
            $this->notificationSender->send(
                $notification->getType(),
                $notification->getRecipient(),
                $notification->getSubject(),
                $content
            );

            $notification->markAsSent();

        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            throw $e;
        } finally {
            $this->notificationRepository->save($notification);
        }

        return [
            'notification_id' => $notification->getNotificationId()->value(),
            'status' => $notification->getStatus(),
        ];
    }
}
```

## API Endpoints

```
POST   /api/v1/notifications/send          - Enviar notificação
GET    /api/v1/notifications               - Listar notificações
GET    /api/v1/notifications/{id}          - Obter notificação
POST   /api/v1/notifications/{id}/retry    - Reenviar notificação

GET    /api/v1/templates                   - Listar templates
POST   /api/v1/templates                   - Criar template
GET    /api/v1/templates/{id}              - Obter template
PUT    /api/v1/templates/{id}              - Atualizar template
```

## Schema do Banco de Dados

```sql
-- Notifications Table
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    template VARCHAR(100) NOT NULL,
    data JSONB,
    status VARCHAR(20) NOT NULL,
    retry_count INTEGER DEFAULT 0,
    error_message TEXT,
    sent_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_status ON notifications(status);
CREATE INDEX idx_notifications_recipient ON notifications(recipient);

-- Templates Table
CREATE TABLE notification_templates (
    id UUID PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    type VARCHAR(20) NOT NULL,
    subject VARCHAR(255),
    content TEXT NOT NULL,
    variables JSONB,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_templates_name ON notification_templates(name);
CREATE INDEX idx_templates_type ON notification_templates(type);
```

## Eventos Consumidos

### user.registered
```php
public function handle(UserRegisteredEvent $event)
{
    $this->sendNotificationUseCase->execute([
        'type' => 'email',
        'recipient' => $event->email,
        'template' => 'welcome_email',
        'data' => [
            'name' => $event->name,
            'email' => $event->email,
        ],
    ]);
}
```

### order.created
```php
public function handle(OrderCreatedEvent $event)
{
    $this->sendNotificationUseCase->execute([
        'type' => 'email',
        'recipient' => $event->customerEmail,
        'template' => 'order_confirmation',
        'data' => [
            'order_id' => $event->orderId,
            'total' => $event->total,
            'items' => $event->items,
        ],
    ]);
}
```

### shipment.dispatched
```php
public function handle(ShipmentDispatchedEvent $event)
{
    $this->sendNotificationUseCase->execute([
        'type' => 'email',
        'recipient' => $event->customerEmail,
        'template' => 'shipment_dispatched',
        'data' => [
            'order_id' => $event->orderId,
            'tracking_code' => $event->trackingCode,
        ],
    ]);
}
```

## Integrações Externas

### Email Providers
- SMTP
- SendGrid
- Amazon SES
- Mailgun

### SMS Providers
- Twilio
- Nexmo
- AWS SNS

### Push Notification Providers
- Firebase Cloud Messaging
- OneSignal
- AWS SNS

---

**Fim da documentação dos microserviços**

