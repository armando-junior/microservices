# Auth Service - Serviço de Autenticação e Autorização

## Visão Geral

O Auth Service é responsável por gerenciar toda a autenticação e autorização do sistema ERP. Implementa Single Sign-On (SSO), gerenciamento de usuários, roles e permissions.

## Bounded Context

**Domínio:** Identidade e Acesso (Identity & Access Management)

### Responsabilidades

- Registro e gerenciamento de usuários
- Autenticação JWT
- Controle de permissões (RBAC - Role-Based Access Control)
- Gestão de roles e permissions
- Token refresh e revogação
- Auditoria de acessos
- Single Sign-On (SSO)

### O que NÃO é responsabilidade

- Dados de negócio (clientes, produtos, vendas)
- Lógica de negócio de outros domínios
- Armazenamento de preferências de usuário avançadas

## Modelo de Domínio

### Entidades

#### User (Aggregate Root)

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;
use DateTimeImmutable;

class User extends BaseEntity
{
    private UserId $userId;
    private string $name;
    private Email $email;
    private string $passwordHash;
    private bool $isActive;
    private ?DateTimeImmutable $emailVerifiedAt;
    private array $roles;

    public function __construct(
        UserId $userId,
        string $name,
        Email $email,
        string $passwordHash
    ) {
        $this->userId = $userId;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->isActive = true;
        $this->emailVerifiedAt = null;
        $this->roles = [];
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function assignRole(Role $role): void
    {
        if (!$this->hasRole($role)) {
            $this->roles[] = $role;
        }
    }

    public function hasRole(Role $role): bool
    {
        foreach ($this->roles as $userRole) {
            if ($userRole->equals($role)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function verifyEmail(): void
    {
        $this->emailVerifiedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Role

```php
<?php

namespace App\Domain\Entities;

class Role extends BaseEntity
{
    private string $name;
    private string $slug;
    private string $description;
    private array $permissions;

    public function __construct(
        string $name,
        string $slug,
        string $description = ''
    ) {
        $this->id = uniqid('role_', true);
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->permissions = [];
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addPermission(Permission $permission): void
    {
        if (!$this->hasPermission($permission->getSlug())) {
            $this->permissions[] = $permission;
        }
    }

    public function hasPermission(string $permissionSlug): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission->getSlug() === $permissionSlug) {
                return true;
            }
        }
        return false;
    }

    // Getters...
}
```

#### Permission

```php
<?php

namespace App\Domain\Entities;

class Permission extends BaseEntity
{
    private string $name;
    private string $slug;
    private string $description;
    private string $module;

    public function __construct(
        string $name,
        string $slug,
        string $module,
        string $description = ''
    ) {
        $this->id = uniqid('perm_', true);
        $this->name = $name;
        $this->slug = $slug;
        $this->module = $module;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

### Value Objects

#### Email

```php
<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class Email
{
    private string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
        
        $this->value = strtolower($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

#### UserId

```php
<?php

namespace App\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;

class UserId
{
    private string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ?? Uuid::uuid4()->toString();
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### Domain Events

```php
<?php

namespace App\Domain\Events;

class UserRegisteredEvent extends DomainEvent
{
    public function __construct(
        private string $userId,
        private string $name,
        private string $email
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'user.registered';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'user_id' => $this->userId,
                'name' => $this->name,
                'email' => $this->email,
            ],
        ];
    }
}

class UserUpdatedEvent extends DomainEvent
{
    public function __construct(
        private string $userId,
        private array $changes
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'user.updated';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'user_id' => $this->userId,
                'changes' => $this->changes,
            ],
        ];
    }
}
```

## Casos de Uso

### 1. Register User

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\User;
use App\Domain\Events\UserRegisteredEvent;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;
use App\Infrastructure\Messaging\Publishers\EventPublisher;

class RegisterUserUseCase implements UseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): array
    {
        // Validar se email já existe
        if ($this->userRepository->findByEmail($input['email'])) {
            throw new \DomainException('Email already registered');
        }

        // Criar usuário
        $user = new User(
            new UserId(),
            $input['name'],
            new Email($input['email']),
            password_hash($input['password'], PASSWORD_BCRYPT)
        );

        // Persistir
        $this->userRepository->save($user);

        // Publicar evento
        $event = new UserRegisteredEvent(
            $user->getUserId()->value(),
            $user->getName(),
            $user->getEmail()->value()
        );
        
        $this->eventPublisher->publish($event);

        return [
            'user_id' => $user->getUserId()->value(),
            'name' => $user->getName(),
            'email' => $user->getEmail()->value(),
        ];
    }
}
```

### 2. Authenticate User

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Auth\JwtTokenGenerator;

class AuthenticateUserUseCase implements UseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private JwtTokenGenerator $tokenGenerator
    ) {}

    public function execute($input): array
    {
        $user = $this->userRepository->findByEmail($input['email']);

        if (!$user) {
            throw new \DomainException('Invalid credentials');
        }

        if (!password_verify($input['password'], $user->getPasswordHash())) {
            throw new \DomainException('Invalid credentials');
        }

        if (!$user->isActive()) {
            throw new \DomainException('User account is inactive');
        }

        // Gerar token JWT
        $token = $this->tokenGenerator->generate($user);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => $user->getUserId()->value(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->value(),
                'roles' => $user->getRoles(),
            ],
        ];
    }
}
```

### 3. Assign Role to User

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\RoleRepositoryInterface;
use App\Domain\ValueObjects\UserId;

class AssignRoleToUserUseCase implements UseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository
    ) {}

    public function execute($input): void
    {
        $user = $this->userRepository->findById(
            new UserId($input['user_id'])
        );

        if (!$user) {
            throw new \DomainException('User not found');
        }

        $role = $this->roleRepository->findBySlug($input['role_slug']);

        if (!$role) {
            throw new \DomainException('Role not found');
        }

        $user->assignRole($role);
        $this->userRepository->save($user);
    }
}
```

## API Endpoints

### Authentication

```
POST   /api/v1/auth/register        - Registrar novo usuário
POST   /api/v1/auth/login           - Fazer login
POST   /api/v1/auth/logout          - Fazer logout
POST   /api/v1/auth/refresh         - Renovar token
POST   /api/v1/auth/verify-email    - Verificar email
POST   /api/v1/auth/forgot-password - Solicitar reset de senha
POST   /api/v1/auth/reset-password  - Resetar senha
GET    /api/v1/auth/me              - Obter usuário autenticado
```

### Users Management

```
GET    /api/v1/users                - Listar usuários
GET    /api/v1/users/{id}           - Obter usuário específico
PUT    /api/v1/users/{id}           - Atualizar usuário
DELETE /api/v1/users/{id}           - Deletar usuário
POST   /api/v1/users/{id}/activate  - Ativar usuário
POST   /api/v1/users/{id}/deactivate - Desativar usuário
```

### Roles & Permissions

```
GET    /api/v1/roles                     - Listar roles
POST   /api/v1/roles                     - Criar role
GET    /api/v1/roles/{id}                - Obter role
PUT    /api/v1/roles/{id}                - Atualizar role
DELETE /api/v1/roles/{id}                - Deletar role
POST   /api/v1/users/{id}/roles          - Atribuir role ao usuário
DELETE /api/v1/users/{id}/roles/{roleId} - Remover role do usuário
GET    /api/v1/permissions               - Listar permissions
```

## Schema do Banco de Dados

```sql
-- Users Table
CREATE TABLE users (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    email_verified_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_is_active ON users(is_active);

-- Roles Table
CREATE TABLE roles (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_roles_slug ON roles(slug);

-- Permissions Table
CREATE TABLE permissions (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_permissions_slug ON permissions(slug);
CREATE INDEX idx_permissions_module ON permissions(module);

-- User Roles (Many-to-Many)
CREATE TABLE user_roles (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    role_id UUID REFERENCES roles(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id)
);

-- Role Permissions (Many-to-Many)
CREATE TABLE role_permissions (
    role_id UUID REFERENCES roles(id) ON DELETE CASCADE,
    permission_id UUID REFERENCES permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (role_id, permission_id)
);

-- Refresh Tokens
CREATE TABLE refresh_tokens (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(500) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_refresh_tokens_user_id ON refresh_tokens(user_id);
CREATE INDEX idx_refresh_tokens_token ON refresh_tokens(token);

-- Audit Log
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100),
    resource_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    changes JSONB,
    created_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
```

## Eventos Publicados

### user.registered

```json
{
  "event_id": "evt_123456",
  "event_name": "user.registered",
  "occurred_at": "2025-10-04T10:30:00Z",
  "payload": {
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "João Silva",
    "email": "joao@example.com"
  }
}
```

### user.updated

```json
{
  "event_id": "evt_123457",
  "event_name": "user.updated",
  "occurred_at": "2025-10-04T10:35:00Z",
  "payload": {
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "changes": {
      "name": {
        "old": "João Silva",
        "new": "João da Silva"
      }
    }
  }
}
```

## Permissões do Sistema

### Módulo: Auth
- `auth.users.view` - Ver usuários
- `auth.users.create` - Criar usuários
- `auth.users.update` - Atualizar usuários
- `auth.users.delete` - Deletar usuários
- `auth.roles.manage` - Gerenciar roles
- `auth.permissions.manage` - Gerenciar permissions

### Módulo: Inventory
- `inventory.products.view`
- `inventory.products.create`
- `inventory.products.update`
- `inventory.products.delete`
- `inventory.stock.view`
- `inventory.stock.update`

### Módulo: Sales
- `sales.orders.view`
- `sales.orders.create`
- `sales.orders.update`
- `sales.orders.cancel`
- `sales.customers.view`
- `sales.customers.create`

### Módulo: Logistics
- `logistics.shipments.view`
- `logistics.shipments.create`
- `logistics.shipments.update`

### Módulo: Financial
- `financial.payments.view`
- `financial.payments.process`
- `financial.invoices.view`
- `financial.invoices.generate`

## Roles Padrão

### Super Admin
- Todas as permissões do sistema

### Admin
- Todas as permissões exceto gerenciamento de super admins

### Manager
- Ver e gerenciar vendas, estoque e logística
- Não pode gerenciar usuários ou financeiro

### Sales
- Ver e criar vendas
- Ver estoque
- Ver clientes

### Stock Manager
- Gerenciar estoque completo
- Ver produtos

### Financial
- Gerenciar financeiro completo
- Ver vendas relacionadas

## Testes

### Testes Unitários

```php
// tests/Unit/Domain/Entities/UserTest.php
public function test_can_assign_role_to_user()
{
    $user = new User(/*...*/);
    $role = new Role('Admin', 'admin');
    
    $user->assignRole($role);
    
    $this->assertTrue($user->hasRole($role));
}

public function test_user_has_permission_through_role()
{
    $permission = new Permission(/*...*/);
    $role = new Role(/*...*/);
    $role->addPermission($permission);
    
    $user = new User(/*...*/);
    $user->assignRole($role);
    
    $this->assertTrue($user->hasPermission($permission->getSlug()));
}
```

### Testes de Feature

```php
// tests/Feature/Auth/RegisterTest.php
public function test_can_register_new_user()
{
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    
    $response->assertStatus(201)
             ->assertJsonStructure([
                 'success',
                 'data' => ['user_id', 'name', 'email']
             ]);
    
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com'
    ]);
}
```

## Resiliência

### Circuit Breaker
- Implementar circuit breaker para chamadas externas (se houver)

### Rate Limiting
```php
// config/rate-limiting.php
return [
    'login' => [
        'attempts' => 5,
        'decay_minutes' => 1,
    ],
    'register' => [
        'attempts' => 3,
        'decay_minutes' => 10,
    ],
];
```

### Caching
- Cache de roles e permissions (TTL: 1 hora)
- Cache de dados de usuário (TTL: 30 minutos)

---

**Próximo:** [Inventory Service](../inventory-service/README.md)

