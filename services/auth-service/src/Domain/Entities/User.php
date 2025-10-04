<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;
use Src\Domain\Events\UserRegistered;
use Src\Domain\Events\UserPasswordChanged;
use Src\Domain\Events\UserUpdated;

/**
 * User Entity
 * 
 * Entidade raiz do agregado User.
 * Contém as regras de negócio relacionadas a usuários.
 */
final class User
{
    private UserId $id;
    private UserName $name;
    private Email $email;
    private Password $password;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $emailVerifiedAt;
    
    /**
     * Domain events que ocorreram nesta entidade
     */
    private array $domainEvents = [];

    private function __construct(
        UserId $id,
        UserName $name,
        Email $email,
        Password $password,
        bool $isActive = true,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $emailVerifiedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt;
        $this->emailVerifiedAt = $emailVerifiedAt;
    }

    /**
     * Cria um novo usuário (factory method)
     */
    public static function create(
        UserId $id,
        UserName $name,
        Email $email,
        Password $password
    ): self {
        $user = new self(
            $id,
            $name,
            $email,
            $password,
            true,
            new DateTimeImmutable()
        );

        // Registra evento de domínio
        $user->recordEvent(new UserRegistered(
            $id,
            $email,
            $name,
            new DateTimeImmutable()
        ));

        return $user;
    }

    /**
     * Reconstitui um usuário existente do banco de dados
     */
    public static function reconstitute(
        UserId $id,
        UserName $name,
        Email $email,
        Password $password,
        bool $isActive,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $emailVerifiedAt = null
    ): self {
        return new self(
            $id,
            $name,
            $email,
            $password,
            $isActive,
            $createdAt,
            $updatedAt,
            $emailVerifiedAt
        );
    }

    /**
     * Altera o nome do usuário
     */
    public function changeName(UserName $newName): void
    {
        if (!$this->name->equals($newName)) {
            $this->name = $newName;
            $this->touch();
            
            $this->recordEvent(new UserUpdated(
                $this->id,
                ['name' => $newName->value()],
                new DateTimeImmutable()
            ));
        }
    }

    /**
     * Altera o e-mail do usuário
     */
    public function changeEmail(Email $newEmail): void
    {
        if (!$this->email->equals($newEmail)) {
            $this->email = $newEmail;
            $this->emailVerifiedAt = null; // Reset email verification
            $this->touch();
            
            $this->recordEvent(new UserUpdated(
                $this->id,
                ['email' => $newEmail->value()],
                new DateTimeImmutable()
            ));
        }
    }

    /**
     * Altera a senha do usuário
     */
    public function changePassword(Password $newPassword): void
    {
        $this->password = $newPassword;
        $this->touch();
        
        $this->recordEvent(new UserPasswordChanged(
            $this->id,
            new DateTimeImmutable()
        ));
    }

    /**
     * Verifica se a senha fornecida está correta
     */
    public function verifyPassword(Password $password): bool
    {
        return $password->matches($this->password->value());
    }

    /**
     * Ativa o usuário
     */
    public function activate(): void
    {
        if (!$this->isActive) {
            $this->isActive = true;
            $this->touch();
            
            $this->recordEvent(new UserUpdated(
                $this->id,
                ['is_active' => true],
                new DateTimeImmutable()
            ));
        }
    }

    /**
     * Desativa o usuário
     */
    public function deactivate(): void
    {
        if ($this->isActive) {
            $this->isActive = false;
            $this->touch();
            
            $this->recordEvent(new UserUpdated(
                $this->id,
                ['is_active' => false],
                new DateTimeImmutable()
            ));
        }
    }

    /**
     * Marca o email como verificado
     */
    public function verifyEmail(): void
    {
        if ($this->emailVerifiedAt === null) {
            $this->emailVerifiedAt = new DateTimeImmutable();
            $this->touch();
            
            $this->recordEvent(new UserUpdated(
                $this->id,
                ['email_verified_at' => $this->emailVerifiedAt],
                new DateTimeImmutable()
            ));
        }
    }

    /**
     * Verifica se o email foi verificado
     */
    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    /**
     * Atualiza a data de modificação
     */
    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Registra um evento de domínio
     */
    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Retorna e limpa os eventos de domínio
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // Getters

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): UserName
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): Password
    {
        return $this->password;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    /**
     * Converte para array (para serialização)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name->value(),
            'email' => $this->email->value(),
            'is_active' => $this->isActive,
            'email_verified_at' => $this->emailVerifiedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

