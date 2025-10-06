<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Application\UseCases\Category\UpdateCategory\UpdateCategoryDTO;
use Src\Application\UseCases\Category\UpdateCategory\UpdateCategoryUseCase;
use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;

class UpdateCategoryUseCaseTest extends TestCase
{
    private CategoryRepositoryInterface $categoryRepository;
    private UpdateCategoryUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->useCase = new UpdateCategoryUseCase($this->categoryRepository);
    }

    public function test_it_updates_category_name(): void
    {
        // Arrange
        $categoryId = CategoryId::generate();
        $category = Category::create(
            $categoryId,
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $dto = new UpdateCategoryDTO(
            id: $categoryId->value(),
            name: 'Electronics and Computers',
            description: null,
            status: null
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn($id) => $id->value() === $categoryId->value()))
            ->willReturn($category);

        $this->categoryRepository
            ->expects($this->once())
            ->method('save')
            ->with($category);

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('Electronics and Computers', $result->name);
    }

    public function test_it_updates_category_description(): void
    {
        // Arrange
        $categoryId = CategoryId::generate();
        $category = Category::create(
            $categoryId,
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $dto = new UpdateCategoryDTO(
            id: $categoryId->value(),
            name: null,
            description: 'Updated description',
            status: null
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($category);

        $this->categoryRepository
            ->expects($this->once())
            ->method('save');

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('Updated description', $result->description);
    }

    public function test_it_updates_category_status_to_inactive(): void
    {
        // Arrange
        $categoryId = CategoryId::generate();
        $category = Category::create(
            $categoryId,
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $dto = new UpdateCategoryDTO(
            id: $categoryId->value(),
            name: null,
            description: null,
            status: 'inactive'
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($category);

        $this->categoryRepository
            ->expects($this->once())
            ->method('save');

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('inactive', $result->status);
    }

    public function test_it_throws_exception_when_category_not_found(): void
    {
        // Arrange
        $categoryId = CategoryId::generate()->value(); // Use valid UUID
        
        $dto = new UpdateCategoryDTO(
            id: $categoryId,
            name: 'Updated Name',
            description: null,
            status: null
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        // Assert
        $this->expectException(CategoryNotFoundException::class);
        $this->expectExceptionMessage("Category with ID {$categoryId} not found.");

        // Act
        $this->useCase->execute($dto);
    }
}
