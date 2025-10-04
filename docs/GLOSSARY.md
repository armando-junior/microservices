# Glossário

## Conceitos Arquiteturais

### Microserviços
Estilo arquitetural que estrutura uma aplicação como uma coleção de serviços pequenos, autônomos e independentemente implantáveis.

### Bounded Context
Conceito do DDD que define os limites de um modelo de domínio. Cada microserviço representa um bounded context.

### Aggregate Root
Entidade raiz de um aggregate no DDD. É o ponto de entrada para todas as operações no aggregate.

### Domain Event
Evento que representa algo significativo que aconteceu no domínio.

### Value Object
Objeto imutável que representa um conceito do domínio definido apenas por seus atributos.

### Repository
Padrão que encapsula a lógica de acesso a dados.

### Use Case
Representa uma ação ou operação de negócio específica.

### DTO (Data Transfer Object)
Objeto usado para transferir dados entre camadas ou serviços.

## Padrões de Comunicação

### Event-Driven Architecture
Arquitetura baseada em produção, detecção, consumo e reação a eventos.

### CQRS (Command Query Responsibility Segregation)
Padrão que separa operações de leitura (queries) de operações de escrita (commands).

### Event Sourcing
Padrão onde mudanças de estado são armazenadas como uma sequência de eventos.

### Saga
Padrão para gerenciar transações distribuídas através de uma sequência de transações locais.

### Message Broker
Intermediário que traduz mensagens entre sistemas (RabbitMQ).

### Exchange
Componente do RabbitMQ que recebe mensagens de publishers e as roteia para queues.

### Queue
Fila que armazena mensagens até que sejam consumidas.

### Routing Key
Chave usada pelo exchange para rotear mensagens para queues específicas.

### Dead Letter Queue (DLQ)
Queue que armazena mensagens que falharam no processamento.

## Padrões de Resiliência

### Circuit Breaker
Padrão que previne cascata de falhas interrompendo chamadas a serviços que estão falhando.

### Retry Pattern
Tentativas automáticas de reexecutar operações que falharam.

### Backoff Exponencial
Estratégia de retry onde o intervalo entre tentativas aumenta exponencialmente.

### Timeout
Limite de tempo para execução de uma operação.

### Bulkhead
Isolamento de recursos para prevenir que falhas em uma parte afetem outras.

### Rate Limiting
Controle da taxa de requisições permitidas.

### Idempotência
Propriedade de operações que podem ser executadas múltiplas vezes sem mudar o resultado.

### Circuit Breaker States
- **Closed:** Funcionamento normal
- **Open:** Bloqueando chamadas (serviço falhando)
- **Half-Open:** Testando recuperação

## Clean Architecture

### Domain Layer
Camada central com regras de negócio e entidades.

### Application Layer
Camada com casos de uso e orquestração de fluxos.

### Infrastructure Layer
Camada com implementações técnicas (database, APIs externas).

### Presentation Layer
Camada de interface com usuário/cliente (controllers, CLI).

## Segurança

### JWT (JSON Web Token)
Token compacto e seguro para autenticação e transmissão de informações.

### RBAC (Role-Based Access Control)
Controle de acesso baseado em papéis/roles.

### API Key
Chave única para autenticação de aplicações.

### CORS (Cross-Origin Resource Sharing)
Mecanismo que permite recursos restritos serem requisitados de outro domínio.

### Hash
Função que transforma dados em string de tamanho fixo.

### Salt
Dados aleatórios adicionados antes de fazer hash de senha.

### Encryption
Processo de codificar informações para que apenas partes autorizadas possam acessá-las.

## Monitoramento

### Metrics
Medições numéricas coletadas ao longo do tempo.

### Logs
Registros de eventos que ocorreram no sistema.

### Tracing
Rastreamento de requisições através de múltiplos serviços.

### Health Check
Endpoint que verifica se um serviço está saudável.

### Prometheus
Sistema de monitoramento e alerta de código aberto.

### Grafana
Plataforma de visualização e análise de métricas.

### Jaeger
Sistema de tracing distribuído.

### ELK Stack
Elasticsearch, Logstash e Kibana para logging centralizado.

## DevOps

### CI/CD
Integração Contínua e Entrega/Deploy Contínuo.

### Docker
Plataforma de containerização.

### Docker Compose
Ferramenta para definir e executar aplicações Docker multi-container.

### Kubernetes
Sistema de orquestração de containers.

### Container
Unidade padrão de software que empacota código e dependências.

### Image
Template imutável para criar containers.

### Volume
Mecanismo para persistir dados gerados por containers.

### Service Discovery
Processo de localizar automaticamente serviços na rede.

## Database

### PostgreSQL
Sistema de gerenciamento de banco de dados relacional.

### Redis
Armazenamento de estrutura de dados em memória.

### Migration
Script que modifica o schema do database de forma versionada.

### Seeder
Script que popula o database com dados iniciais.

### Index
Estrutura que melhora a velocidade de recuperação de dados.

### Transaction
Sequência de operações tratadas como uma única unidade.

### ACID
Atomicidade, Consistência, Isolamento e Durabilidade.

## Laravel Específico

### Eloquent
ORM (Object-Relational Mapping) do Laravel.

### Artisan
Interface de linha de comando do Laravel.

### Sanctum
Sistema de autenticação API do Laravel.

### Horizon
Dashboard e configuração para filas do Laravel.

### Telescope
Assistente de debugging do Laravel.

### Service Provider
Classe que registra bindings no container de serviço.

### Middleware
Camada que filtra requisições HTTP.

### Form Request
Classe para validação de requisições.

### API Resource
Transformação de modelos e coleções em JSON.

## Business Domain

### SKU (Stock Keeping Unit)
Identificador único de produto.

### NF-e (Nota Fiscal Eletrônica)
Documento fiscal eletrônico do Brasil.

### Gateway de Pagamento
Serviço que processa transações de cartão de crédito.

### Transportadora
Empresa que realiza entregas.

### Frete
Custo de transporte de mercadorias.

### Estoque
Quantidade de produtos disponíveis.

### Reserva
Separação temporária de estoque para um pedido.

### Pedido
Solicitação de compra de produtos.

### Cliente
Pessoa ou empresa que compra produtos.

### Fornecedor
Pessoa ou empresa que fornece produtos.

## Abreviações Comuns

- **API:** Application Programming Interface
- **REST:** Representational State Transfer
- **HTTP:** Hypertext Transfer Protocol
- **HTTPS:** HTTP Secure
- **JSON:** JavaScript Object Notation
- **XML:** eXtensible Markup Language
- **UUID:** Universally Unique Identifier
- **URL:** Uniform Resource Locator
- **TTL:** Time To Live
- **HA:** High Availability
- **SLA:** Service Level Agreement
- **SLO:** Service Level Objective
- **RTO:** Recovery Time Objective
- **RPO:** Recovery Point Objective
- **MVP:** Minimum Viable Product
- **UAT:** User Acceptance Testing
- **TDD:** Test-Driven Development
- **BDD:** Behavior-Driven Development
- **DDD:** Domain-Driven Design
- **SOLID:** Single responsibility, Open-closed, Liskov substitution, Interface segregation, Dependency inversion

---

**Nota:** Este glossário está em constante evolução. Sinta-se livre para adicionar novos termos conforme necessário.

