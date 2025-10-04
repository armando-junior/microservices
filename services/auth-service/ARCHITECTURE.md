# 🏗️ Auth Service - Clean Architecture

## 📁 Estrutura do Projeto

```
auth-service/
├── src/
│   ├── Domain/                    # Camada de Domínio (Regras de Negócio)
│   │   ├── Entities/              # Entidades do domínio
│   │   ├── ValueObjects/          # Objetos de valor imutáveis
│   │   ├── Repositories/          # Interfaces de repositórios
│   │   ├── Events/                # Eventos de domínio
│   │   └── Exceptions/            # Exceções de domínio
│   │
│   ├── Application/               # Camada de Aplicação (Casos de Uso)
│   │   ├── UseCases/              # Casos de uso (Register, Login, etc)
│   │   ├── DTOs/                  # Data Transfer Objects
│   │   ├── Services/              # Serviços de aplicação
│   │   └── Contracts/             # Interfaces de serviços
│   │
│   ├── Infrastructure/            # Camada de Infraestrutura (Detalhes Técnicos)
│   │   ├── Persistence/
│   │   │   └── Eloquent/          # Repositórios Eloquent
│   │   ├── Messaging/
│   │   │   └── RabbitMQ/          # Cliente RabbitMQ
│   │   ├── Cache/                 # Implementações de cache
│   │   └── Logging/               # Configurações de logging
│   │
│   └── Presentation/              # Camada de Apresentação (API)
│       ├── Controllers/           # Controllers HTTP
│       ├── Requests/              # Form Requests (validação)
│       ├── Resources/             # API Resources (transformação)
│       └── Middleware/            # Middlewares customizados
│
├── app/                           # Laravel padrão (minimizado)
├── config/                        # Configurações
├── database/                      # Migrations e Seeds
├── tests/                         # Testes
└── routes/                        # Rotas da API
```

## 🎯 Princípios da Clean Architecture

### 1. **Independência de Frameworks**
- O domínio não depende do Laravel
- Regras de negócio isoladas

### 2. **Testabilidade**
- Casos de uso testáveis sem infraestrutura
- Mocks fáceis de criar

### 3. **Independência de UI**
- A lógica não depende da apresentação
- Pode ter múltiplas interfaces (REST, GraphQL, CLI)

### 4. **Independência de Database**
- Regras de negócio não conhecem o banco
- Fácil trocar PostgreSQL por outro banco

### 5. **Independência de Agentes Externos**
- RabbitMQ, Redis, APIs externas são detalhes
- Interfaces bem definidas

## 📦 Camadas e Dependências

```
┌─────────────────────────────────────────────────┐
│           Presentation (API Layer)               │  ← Controllers, Requests, Resources
├─────────────────────────────────────────────────┤
│          Application (Use Cases)                 │  ← Register, Login, Logout
├─────────────────────────────────────────────────┤
│            Domain (Business Logic)               │  ← User, Email, Password, Roles
├─────────────────────────────────────────────────┤
│     Infrastructure (Technical Details)           │  ← Eloquent, RabbitMQ, Redis
└─────────────────────────────────────────────────┘

Direção de dependência: ↑ (sempre para dentro)
```

**Regra de Ouro:** Camadas externas podem depender de camadas internas, mas **NUNCA** o contrário.

## 🔄 Fluxo de uma Requisição

```
1. HTTP Request
   ↓
2. Controller (Presentation)
   ↓
3. Use Case (Application)
   ↓
4. Entity + Repository (Domain)
   ↓
5. Repository Implementation (Infrastructure)
   ↓
6. Database
```

**Exemplo: Register User**

```php
// 1. Request chega no Controller
POST /api/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123"
}

// 2. Controller (Presentation)
RegisterController::register(RegisterRequest $request)
  → Valida request
  → Chama Use Case

// 3. Use Case (Application)
RegisterUserUseCase::execute(RegisterUserDTO $dto)
  → Cria entidade User
  → Valida regras de negócio
  → Salva via Repository
  → Publica evento UserRegistered

// 4. Repository (Domain - Interface)
UserRepositoryInterface::save(User $user)

// 5. Eloquent Repository (Infrastructure)
EloquentUserRepository::save(User $user)
  → Converte Entity → Eloquent Model
  → Persiste no PostgreSQL

// 6. Event Handler (Infrastructure)
UserRegistered → RabbitMQ → Notification Service
```

## 📋 Componentes Principais

### Domain Layer

**Entities (Entidades)**
```php
// User.php - Entidade do domínio
class User {
    private UserId $id;
    private Email $email;
    private Password $password;
    private UserName $name;
    private Roles $roles;
    
    public function register(): void;
    public function changePassword(Password $newPassword): void;
    public function assignRole(Role $role): void;
}
```

**Value Objects**
```php
// Email.php - Objeto de valor
final class Email {
    private string $value;
    
    public function __construct(string $email) {
        $this->validate($email);
        $this->value = $email;
    }
    
    private function validate(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }
    }
}
```

**Repository Interfaces**
```php
// UserRepositoryInterface.php
interface UserRepositoryInterface {
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function existsByEmail(Email $email): bool;
}
```

### Application Layer

**Use Cases**
```php
// RegisterUserUseCase.php
class RegisterUserUseCase {
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
        private PasswordHasherInterface $passwordHasher
    ) {}
    
    public function execute(RegisterUserDTO $dto): UserDTO {
        // 1. Validate email is unique
        if ($this->userRepository->existsByEmail($dto->email)) {
            throw new EmailAlreadyExistsException();
        }
        
        // 2. Create user entity
        $user = User::create(
            UserId::generate(),
            new Email($dto->email),
            $this->passwordHasher->hash($dto->password),
            new UserName($dto->name)
        );
        
        // 3. Save user
        $this->userRepository->save($user);
        
        // 4. Dispatch event
        $this->eventDispatcher->dispatch(
            new UserRegistered($user->getId(), $user->getEmail())
        );
        
        // 5. Return DTO
        return UserDTO::fromEntity($user);
    }
}
```

**DTOs (Data Transfer Objects)**
```php
// RegisterUserDTO.php
class RegisterUserDTO {
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password
    ) {}
    
    public static function fromRequest(RegisterRequest $request): self {
        return new self(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password')
        );
    }
}
```

### Infrastructure Layer

**Eloquent Repository**
```php
// EloquentUserRepository.php
class EloquentUserRepository implements UserRepositoryInterface {
    public function save(User $user): void {
        UserModel::updateOrCreate(
            ['id' => $user->getId()->value()],
            [
                'name' => $user->getName()->value(),
                'email' => $user->getEmail()->value(),
                'password' => $user->getPassword()->hash(),
            ]
        );
    }
    
    public function findByEmail(Email $email): ?User {
        $model = UserModel::where('email', $email->value())->first();
        
        return $model ? $this->toDomain($model) : null;
    }
    
    private function toDomain(UserModel $model): User {
        return User::reconstitute(
            new UserId($model->id),
            new Email($model->email),
            Password::fromHash($model->password),
            new UserName($model->name),
            Roles::fromArray($model->roles)
        );
    }
}
```

**RabbitMQ Publisher**
```php
// RabbitMQEventPublisher.php
class RabbitMQEventPublisher implements EventPublisherInterface {
    public function publish(DomainEvent $event): void {
        $this->channel->basic_publish(
            new AMQPMessage(json_encode($event->toArray())),
            'auth.events',
            $event->routingKey()
        );
    }
}
```

### Presentation Layer

**Controller**
```php
// RegisterController.php
class RegisterController extends Controller {
    public function __construct(
        private RegisterUserUseCase $registerUseCase
    ) {}
    
    public function register(RegisterRequest $request): JsonResponse {
        $dto = RegisterUserDTO::fromRequest($request);
        
        $user = $this->registerUseCase->execute($dto);
        
        return response()->json(
            new UserResource($user),
            201
        );
    }
}
```

**Request Validation**
```php
// RegisterRequest.php
class RegisterRequest extends FormRequest {
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

## 🧪 Testes

### Unit Tests (Domain & Application)
```php
// tests/Unit/Domain/ValueObjects/EmailTest.php
test('should create valid email', function () {
    $email = new Email('test@example.com');
    expect($email->value())->toBe('test@example.com');
});

test('should throw exception for invalid email', function () {
    new Email('invalid-email');
})->throws(InvalidEmailException::class);
```

### Integration Tests (Infrastructure)
```php
// tests/Integration/Infrastructure/EloquentUserRepositoryTest.php
test('should save and retrieve user', function () {
    $user = User::create(/* ... */);
    
    $this->repository->save($user);
    
    $found = $this->repository->findById($user->getId());
    expect($found)->not->toBeNull();
    expect($found->getEmail())->toEqual($user->getEmail());
});
```

### Feature Tests (End-to-End)
```php
// tests/Feature/Auth/RegisterTest.php
test('should register new user', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});
```

## 🔐 CQRS Pattern (Opcional/Avançado)

Para operações de leitura complexas, podemos adicionar CQRS:

```
src/Application/
├── Commands/           # Write operations (Create, Update, Delete)
│   ├── RegisterUser/
│   ├── LoginUser/
│   └── ChangePassword/
│
└── Queries/           # Read operations (Get, List, Find)
    ├── GetUserById/
    ├── ListUsers/
    └── FindUserByEmail/
```

## 🎯 Vantagens desta Arquitetura

✅ **Manutenibilidade**: Fácil de entender e modificar
✅ **Testabilidade**: Testes isolados sem dependências
✅ **Escalabilidade**: Adicionar features sem quebrar existentes
✅ **Flexibilidade**: Trocar tecnologias sem afetar negócio
✅ **Clareza**: Separação clara de responsabilidades

## 📚 Referências

- [Clean Architecture by Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Domain-Driven Design by Eric Evans](https://www.domainlanguage.com/ddd/)
- [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/)

