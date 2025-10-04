# Infraestrutura e Ferramentas

## Stack Tecnológico Completo

### Backend Framework
- **Laravel 11.x**
  - PHP 8.3+
  - Composer 2.x
  - Laravel Sanctum (API Authentication)
  - Laravel Horizon (Queue Management)
  - Laravel Telescope (Debugging)

### Bancos de Dados
- **PostgreSQL 16+**
  - Um banco por microserviço
  - Replicação master-slave para leitura
  - Backup automatizado

### Message Broker
- **RabbitMQ 3.13+**
  - Exchanges para cada domínio
  - Dead Letter Queues
  - Priority Queues
  - Message TTL
  - Management Plugin

### Cache e Session Store
- **Redis 7.2+**
  - Cache de aplicação
  - Session storage
  - Rate limiting
  - Pub/Sub para eventos rápidos

### API Gateway
- **Kong Gateway** (Recomendado) ou **Traefik**
  - Rate limiting
  - Authentication
  - Service routing
  - Load balancing
  - Logging

### Containerização
- **Docker 24+**
- **Docker Compose 2.x**
- **Kubernetes** (opcional para produção)

### Monitoramento e Observabilidade

#### Logs
- **Elasticsearch 8.x**
- **Logstash 8.x**
- **Kibana 8.x**
- **Filebeat** (Log shipping)

#### Métricas
- **Prometheus 2.x**
- **Grafana 10+**
- **Node Exporter**
- **Redis Exporter**
- **PostgreSQL Exporter**

#### Tracing
- **Jaeger**
- **OpenTelemetry**

#### Health Checks
- Laravel Health Check Package
- Custom health endpoints

### CI/CD
- **GitLab CI** ou **GitHub Actions**
- **Docker Registry** (Harbor ou GitLab Registry)
- **Automated Testing**
- **Code Quality:** PHPStan, PHP CS Fixer

### Documentação
- **Swagger/OpenAPI 3.0**
- **Postman Collections**
- **Markdown Documentation**

## Arquitetura de Infraestrutura

```
┌─────────────────────────────────────────────────────────────┐
│                      Load Balancer                           │
│                        (Nginx/HAProxy)                       │
└────────────────────────────┬────────────────────────────────┘
                             │
                    ┌────────▼─────────┐
                    │   API Gateway    │
                    │   (Kong/Traefik) │
                    └────────┬─────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
┌────────▼────────┐  ┌──────▼──────┐  ┌────────▼────────┐
│  Auth Service   │  │  Inventory  │  │  Sales Service  │
│  Container      │  │  Container  │  │   Container     │
├─────────────────┤  ├─────────────┤  ├─────────────────┤
│  PostgreSQL     │  │ PostgreSQL  │  │   PostgreSQL    │
│  (auth_db)      │  │(inventory_db│  │   (sales_db)    │
└─────────────────┘  └─────────────┘  └─────────────────┘

┌────────▼────────┐  ┌──────▼──────┐  ┌────────▼────────┐
│   Logistics     │  │  Financial  │  │  Notification   │
│   Container     │  │  Container  │  │   Container     │
├─────────────────┤  ├─────────────┤  ├─────────────────┤
│  PostgreSQL     │  │ PostgreSQL  │  │      N/A        │
│ (logistics_db)  │  │(financial_db│  │   (Stateless)   │
└─────────────────┘  └─────────────┘  └─────────────────┘

┌──────────────────────────────────────────────────────────┐
│                   Shared Infrastructure                   │
├──────────────┬─────────────┬──────────────┬──────────────┤
│   RabbitMQ   │    Redis    │   Jaeger     │   ELK Stack  │
│   Cluster    │   Cluster   │   Tracing    │   Logging    │
└──────────────┴─────────────┴──────────────┴──────────────┘

┌──────────────────────────────────────────────────────────┐
│                    Monitoring Layer                       │
├──────────────────────┬───────────────────────────────────┤
│    Prometheus        │           Grafana                 │
└──────────────────────┴───────────────────────────────────┘
```

## Docker Compose - Estrutura

### Arquivo Principal: `docker-compose.yml`

```yaml
version: '3.9'

services:
  # API Gateway
  api-gateway:
    image: kong:3.4
    ports:
      - "8000:8000"
      - "8443:8443"
      - "8001:8001"
    environment:
      KONG_DATABASE: postgres
      KONG_PG_HOST: gateway-db
    networks:
      - microservices-net
    depends_on:
      - gateway-db

  gateway-db:
    image: postgres:16
    environment:
      POSTGRES_DB: kong
      POSTGRES_USER: kong
      POSTGRES_PASSWORD: kong
    volumes:
      - gateway-db-data:/var/lib/postgresql/data
    networks:
      - microservices-net

  # Message Broker
  rabbitmq:
    image: rabbitmq:3.13-management
    ports:
      - "5672:5672"
      - "15672:15672"
    environment:
      RABBITMQ_DEFAULT_USER: admin
      RABBITMQ_DEFAULT_PASS: admin123
    volumes:
      - rabbitmq-data:/var/lib/rabbitmq
    networks:
      - microservices-net
    healthcheck:
      test: rabbitmq-diagnostics -q ping
      interval: 30s
      timeout: 10s
      retries: 3

  # Cache & Session
  redis:
    image: redis:7.2-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - microservices-net
    command: redis-server --appendonly yes

  # Auth Service
  auth-service:
    build:
      context: ./auth-service
      dockerfile: Dockerfile
    ports:
      - "8001:8000"
    environment:
      DB_HOST: auth-db
      DB_DATABASE: auth_db
      REDIS_HOST: redis
      RABBITMQ_HOST: rabbitmq
    networks:
      - microservices-net
    depends_on:
      - auth-db
      - redis
      - rabbitmq

  auth-db:
    image: postgres:16
    environment:
      POSTGRES_DB: auth_db
      POSTGRES_USER: auth_user
      POSTGRES_PASSWORD: auth_pass
    volumes:
      - auth-db-data:/var/lib/postgresql/data
    networks:
      - microservices-net

  # Inventory Service
  inventory-service:
    build:
      context: ./inventory-service
      dockerfile: Dockerfile
    ports:
      - "8002:8000"
    environment:
      DB_HOST: inventory-db
      DB_DATABASE: inventory_db
      REDIS_HOST: redis
      RABBITMQ_HOST: rabbitmq
    networks:
      - microservices-net
    depends_on:
      - inventory-db
      - redis
      - rabbitmq

  inventory-db:
    image: postgres:16
    environment:
      POSTGRES_DB: inventory_db
      POSTGRES_USER: inventory_user
      POSTGRES_PASSWORD: inventory_pass
    volumes:
      - inventory-db-data:/var/lib/postgresql/data
    networks:
      - microservices-net

  # Sales Service
  sales-service:
    build:
      context: ./sales-service
      dockerfile: Dockerfile
    ports:
      - "8003:8000"
    environment:
      DB_HOST: sales-db
      DB_DATABASE: sales_db
      REDIS_HOST: redis
      RABBITMQ_HOST: rabbitmq
    networks:
      - microservices-net
    depends_on:
      - sales-db
      - redis
      - rabbitmq

  sales-db:
    image: postgres:16
    environment:
      POSTGRES_DB: sales_db
      POSTGRES_USER: sales_user
      POSTGRES_PASSWORD: sales_pass
    volumes:
      - sales-db-data:/var/lib/postgresql/data
    networks:
      - microservices-net

  # Logistics Service
  logistics-service:
    build:
      context: ./logistics-service
      dockerfile: Dockerfile
    ports:
      - "8004:8000"
    environment:
      DB_HOST: logistics-db
      DB_DATABASE: logistics_db
      REDIS_HOST: redis
      RABBITMQ_HOST: rabbitmq
    networks:
      - microservices-net
    depends_on:
      - logistics-db
      - redis
      - rabbitmq

  logistics-db:
    image: postgres:16
    environment:
      POSTGRES_DB: logistics_db
      POSTGRES_USER: logistics_user
      POSTGRES_PASSWORD: logistics_pass
    volumes:
      - logistics-db-data:/var/lib/postgresql/data
    networks:
      - microservices-net

  # Financial Service
  financial-service:
    build:
      context: ./financial-service
      dockerfile: Dockerfile
    ports:
      - "8005:8000"
    environment:
      DB_HOST: financial-db
      DB_DATABASE: financial_db
      REDIS_HOST: redis
      RABBITMQ_HOST: rabbitmq
    networks:
      - microservices-net
    depends_on:
      - financial-db
      - redis
      - rabbitmq

  financial-db:
    image: postgres:16
    environment:
      POSTGRES_DB: financial_db
      POSTGRES_USER: financial_user
      POSTGRES_PASSWORD: financial_pass
    volumes:
      - financial-db-data:/var/lib/postgresql/data
    networks:
      - microservices-net

  # Monitoring - Prometheus
  prometheus:
    image: prom/prometheus:latest
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus-data:/prometheus
    networks:
      - microservices-net

  # Monitoring - Grafana
  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      GF_SECURITY_ADMIN_PASSWORD: admin
    volumes:
      - grafana-data:/var/lib/grafana
    networks:
      - microservices-net
    depends_on:
      - prometheus

  # Tracing - Jaeger
  jaeger:
    image: jaegertracing/all-in-one:latest
    ports:
      - "5775:5775/udp"
      - "6831:6831/udp"
      - "6832:6832/udp"
      - "5778:5778"
      - "16686:16686"
      - "14268:14268"
      - "14250:14250"
      - "9411:9411"
    networks:
      - microservices-net

  # Logging - Elasticsearch
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    environment:
      - discovery.type=single-node
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
      - xpack.security.enabled=false
    ports:
      - "9200:9200"
    volumes:
      - elasticsearch-data:/usr/share/elasticsearch/data
    networks:
      - microservices-net

  # Logging - Logstash
  logstash:
    image: docker.elastic.co/logstash/logstash:8.11.0
    ports:
      - "5044:5044"
      - "9600:9600"
    volumes:
      - ./logging/logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    networks:
      - microservices-net
    depends_on:
      - elasticsearch

  # Logging - Kibana
  kibana:
    image: docker.elastic.co/kibana/kibana:8.11.0
    ports:
      - "5601:5601"
    environment:
      ELASTICSEARCH_URL: http://elasticsearch:9200
    networks:
      - microservices-net
    depends_on:
      - elasticsearch

networks:
  microservices-net:
    driver: bridge

volumes:
  gateway-db-data:
  rabbitmq-data:
  redis-data:
  auth-db-data:
  inventory-db-data:
  sales-db-data:
  logistics-db-data:
  financial-db-data:
  prometheus-data:
  grafana-data:
  elasticsearch-data:
```

## Dockerfile Base para Microserviços Laravel

```dockerfile
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    rabbitmq-c-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql zip gd bcmath

# Install AMQP extension for RabbitMQ
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install amqp redis \
    && docker-php-ext-enable amqp redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
```

## Configuração RabbitMQ

### Exchanges e Queues

```
Exchanges:
├── auth.events         (topic)
├── inventory.events    (topic)
├── sales.events        (topic)
├── logistics.events    (topic)
└── financial.events    (topic)

Queues por serviço:
├── auth.queue
├── inventory.queue
├── sales.queue
├── logistics.queue
├── financial.queue
└── notification.queue

Dead Letter Queues:
├── auth.dlq
├── inventory.dlq
├── sales.dlq
├── logistics.dlq
└── financial.dlq
```

### Routing Keys Pattern

```
{domain}.{entity}.{action}

Exemplos:
- sales.order.created
- inventory.stock.updated
- logistics.shipment.dispatched
- financial.payment.processed
- auth.user.registered
```

## Monitoramento - Prometheus Config

```yaml
# prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'auth-service'
    static_configs:
      - targets: ['auth-service:9090']
  
  - job_name: 'inventory-service'
    static_configs:
      - targets: ['inventory-service:9090']
  
  - job_name: 'sales-service'
    static_configs:
      - targets: ['sales-service:9090']
  
  - job_name: 'logistics-service'
    static_configs:
      - targets: ['logistics-service:9090']
  
  - job_name: 'financial-service'
    static_configs:
      - targets: ['financial-service:9090']
  
  - job_name: 'rabbitmq'
    static_configs:
      - targets: ['rabbitmq:15692']
  
  - job_name: 'redis'
    static_configs:
      - targets: ['redis-exporter:9121']
  
  - job_name: 'postgres'
    static_configs:
      - targets: ['postgres-exporter:9187']
```

## Health Checks

### Endpoint em cada serviço

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => config('app.name'),
        'timestamp' => now(),
        'checks' => [
            'database' => DB::connection()->getPdo() ? 'ok' : 'fail',
            'redis' => Redis::ping() ? 'ok' : 'fail',
            'rabbitmq' => app('rabbitmq')->isConnected() ? 'ok' : 'fail',
        ]
    ]);
});
```

## Requisitos de Sistema

### Desenvolvimento
- **CPU:** 4+ cores
- **RAM:** 16GB+
- **Disco:** 50GB+ SSD
- **Docker:** 24+
- **Docker Compose:** 2.x

### Produção (Mínimo por Serviço)
- **CPU:** 2 cores
- **RAM:** 2GB
- **Disco:** 20GB

### Produção (Infraestrutura Compartilhada)
- **RabbitMQ:** 4GB RAM, 2 cores
- **PostgreSQL (cada):** 2GB RAM, 2 cores
- **Redis:** 1GB RAM, 1 core
- **ELK Stack:** 8GB RAM, 4 cores
- **Monitoring:** 4GB RAM, 2 cores

## Segurança

### Variáveis de Ambiente
- Usar `.env` files
- Nunca commitar credenciais
- Usar secrets management (Docker Secrets, Kubernetes Secrets)

### Network Security
- Isolation entre serviços
- TLS/SSL em produção
- Firewall rules
- VPC/Private networks

### Database Security
- Usuários com permissões mínimas
- Conexões criptografadas
- Backup encryption
- Audit logging

---

**Próximo:** [Microserviços Detalhados](../03-microservices/README.md)

