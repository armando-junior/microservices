<?php

declare(strict_types=1);

namespace Src\Domain\Entities;

use DateTimeImmutable;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;

/**
 * Category Entity (Aggregate Root)
 * 
 * Representa uma categoria de produtos.
 */
final class Category
{
    private const STATUS_ACTIVE = 'active';
    private const STATUS_INACTIVE = 'inactive';

    private array $domainEvents = [];

    private function __construct(
        private readonly CategoryId $id,
        private CategoryName $name,
        private string $slug,
        private ?string $description = null,
        private string $status = self::STATUS_ACTIVE,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = null
    ) {
    }

    /**
     * Cria uma nova categoria
     */
    public static function create(
        CategoryId $id,
        CategoryName $name,
        ?string $description = null
    ): self {
        $slug = self::generateSlug($name->value());

        $category = new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            status: self::STATUS_ACTIVE,
            createdAt: new DateTimeImmutable()
        );

        $category->recordEvent('CategoryCreated', [
            'category_id' => $id->value(),
            'name' => $name->value(),
            'slug' => $slug,
        ]);

        return $category;
    }

    /**
     * Reconstitui uma categoria do banco de dados
     */
    public static function reconstitute(
        CategoryId $id,
        CategoryName $name,
        string $slug,
        ?string $description,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Atualiza o nome da categoria
     */
    public function updateName(CategoryName $name): void
    {
        if ($this->name->equals($name)) {
            return;
        }

        $this->name = $name;
        $this->slug = self::generateSlug($name->value());
        $this->touch();

        $this->recordEvent('CategoryUpdated', [
            'category_id' => $this->id->value(),
            'field' => 'name',
            'new_value' => $name->value(),
        ]);
    }

    /**
     * Atualiza a descrição
     */
    public function updateDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    /**
     * Ativa a categoria
     */
    public function activate(): void
    {
        if ($this->isActive()) {
            return;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->touch();

        $this->recordEvent('CategoryActivated', [
            'category_id' => $this->id->value(),
        ]);
    }

    /**
     * Desativa a categoria
     */
    public function deactivate(): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->status = self::STATUS_INACTIVE;
        $this->touch();

        $this->recordEvent('CategoryDeactivated', [
            'category_id' => $this->id->value(),
        ]);
    }

    /**
     * Verifica se a categoria está ativa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Gera um slug a partir do nome
     */
    private static function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * Atualiza o timestamp de updated_at
     */
    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Registra um evento de domínio
     */
    private function recordEvent(string $eventName, array $data): void
    {
        $this->domainEvents[] = [
            'event' => $eventName,
            'data' => $data,
            'occurred_at' => new DateTimeImmutable(),
        ];
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

    public function getId(): CategoryId
    {
        return $this->id;
    }

    public function getName(): CategoryName
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name->value(),
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

