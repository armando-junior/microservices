# Guia de Início Rápido

## Pré-requisitos

- Docker 24+
- Docker Compose 2.x
- Git
- PHP 8.3+ (para desenvolvimento local)
- Composer 2.x
- Node.js 20+ (opcional, para ferramentas)

## Setup Inicial (Sprint 0)

### 1. Clone o Repositório

```bash
git clone <repository-url>
cd microservices
```

### 2. Configure Variáveis de Ambiente

```bash
# Copiar .env.example para cada serviço
cp .env.example .env

# Editar conforme necessário
nano .env
```

### 3. Iniciar Infraestrutura

```bash
# Iniciar todos os containers
docker-compose up -d

# Verificar status
docker-compose ps

# Ver logs
docker-compose logs -f
```

### 4. Acessar Serviços

- **API Gateway:** http://localhost:8000
- **RabbitMQ Management:** http://localhost:15672 (admin/admin123)
- **Grafana:** http://localhost:3000 (admin/admin)
- **Prometheus:** http://localhost:9090
- **Kibana:** http://localhost:5601
- **Jaeger:** http://localhost:16686

### 5. Executar Migrations

```bash
# Para cada serviço
docker-compose exec auth-service php artisan migrate
docker-compose exec inventory-service php artisan migrate
docker-compose exec sales-service php artisan migrate
docker-compose exec logistics-service php artisan migrate
docker-compose exec financial-service php artisan migrate
```

### 6. Executar Seeders

```bash
# Seed de dados iniciais
docker-compose exec auth-service php artisan db:seed
docker-compose exec inventory-service php artisan db:seed
```

### 7. Testar Health Checks

```bash
# Auth Service
curl http://localhost:8001/api/health

# Inventory Service
curl http://localhost:8002/api/health

# Sales Service
curl http://localhost:8003/api/health

# Logistics Service
curl http://localhost:8004/api/health

# Financial Service
curl http://localhost:8005/api/health
```

## Comandos Úteis

### Docker

```bash
# Parar todos os containers
docker-compose down

# Rebuild de um serviço específico
docker-compose build auth-service

# Restart de um serviço
docker-compose restart auth-service

# Ver logs de um serviço
docker-compose logs -f auth-service

# Executar comando em um container
docker-compose exec auth-service bash
```

### Laravel Artisan

```bash
# Limpar cache
docker-compose exec auth-service php artisan cache:clear

# Executar testes
docker-compose exec auth-service php artisan test

# Criar migration
docker-compose exec auth-service php artisan make:migration create_users_table

# Rollback migrations
docker-compose exec auth-service php artisan migrate:rollback
```

### RabbitMQ

```bash
# Listar queues
docker-compose exec rabbitmq rabbitmqctl list_queues

# Listar exchanges
docker-compose exec rabbitmq rabbitmqctl list_exchanges

# Purgar queue
docker-compose exec rabbitmq rabbitmqctl purge_queue inventory.queue
```

### Queue Workers

```bash
# Iniciar worker
docker-compose exec inventory-service php artisan queue:work rabbitmq

# Worker com verbose
docker-compose exec inventory-service php artisan queue:work rabbitmq --verbose

# Restart workers
docker-compose exec inventory-service php artisan queue:restart
```

## Fluxo de Desenvolvimento

### 1. Criar Feature Branch

```bash
git checkout -b feature/nome-da-feature
```

### 2. Desenvolver

```bash
# Fazer alterações
# Escrever testes
# Executar testes
docker-compose exec auth-service php artisan test
```

### 3. Commit

```bash
git add .
git commit -m "feat: descrição da feature"
```

### 4. Push e Pull Request

```bash
git push origin feature/nome-da-feature
# Criar PR no GitHub/GitLab
```

## Testando o Sistema Completo

### 1. Registrar Usuário

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Fazer Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "password123"
  }'

# Salvar o token retornado
export TOKEN="seu-token-aqui"
```

### 3. Criar Produto

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Produto Teste",
    "description": "Descrição do produto",
    "sku": "PROD-001",
    "price": 99.90
  }'
```

### 4. Adicionar Estoque

```bash
curl -X POST http://localhost:8000/api/v1/stock/add \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "product_id": "id-do-produto",
    "quantity": 100
  }'
```

### 5. Criar Cliente

```bash
curl -X POST http://localhost:8000/api/v1/customers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Maria Santos",
    "email": "maria@example.com",
    "phone": "11999999999"
  }'
```

### 6. Criar Pedido

```bash
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "customer_id": "id-do-cliente",
    "items": [
      {
        "product_id": "id-do-produto",
        "product_name": "Produto Teste",
        "quantity": 2,
        "unit_price": 99.90
      }
    ]
  }'
```

### 7. Verificar Status do Pedido

```bash
curl http://localhost:8000/api/v1/orders/{order_id} \
  -H "Authorization: Bearer $TOKEN"
```

## Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker-compose logs service-name

# Verificar se a porta está em uso
sudo lsof -i :8001

# Rebuild do container
docker-compose build --no-cache service-name
docker-compose up -d service-name
```

### Erro de conexão com banco de dados

```bash
# Verificar se PostgreSQL está rodando
docker-compose ps postgres

# Testar conexão
docker-compose exec auth-service php artisan tinker
>>> DB::connection()->getPdo();
```

### Erro de conexão com RabbitMQ

```bash
# Verificar se RabbitMQ está rodando
docker-compose ps rabbitmq

# Ver logs do RabbitMQ
docker-compose logs rabbitmq

# Testar conexão
curl http://localhost:15672/api/overview
```

### Queue não está processando

```bash
# Verificar workers
docker-compose ps | grep queue

# Restart workers
docker-compose restart inventory-service
docker-compose exec inventory-service php artisan queue:restart

# Ver jobs failed
docker-compose exec inventory-service php artisan queue:failed
```

### Limpar tudo e recomeçar

```bash
# Parar e remover containers
docker-compose down -v

# Remover volumes
docker volume prune

# Rebuild tudo
docker-compose build --no-cache

# Iniciar novamente
docker-compose up -d

# Executar migrations
docker-compose exec auth-service php artisan migrate:fresh --seed
```

## Próximos Passos

1. Ler [Documentação de Arquitetura](./01-architecture/README.md)
2. Revisar [Documentação dos Microserviços](./03-microservices/README.md)
3. Iniciar [Sprint 1](./06-sprints/README.md#sprint-1-auth-service---base)
4. Configurar ambiente de desenvolvimento local
5. Executar testes

## Recursos Adicionais

- [Glossário](./GLOSSARY.md)
- [Documentação da API (Swagger)](http://localhost:8000/api/documentation)
- [RabbitMQ Tutorials](https://www.rabbitmq.com/getstarted.html)
- [Laravel Documentation](https://laravel.com/docs/11.x)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

## Suporte

- Criar issue no repositório
- Consultar documentação em `/docs`
- Verificar logs em `/storage/logs`

---

**Boa sorte com o desenvolvimento! 🚀**

