<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use DomainException;
use PHPUnit\Framework\TestCase;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Application\UseCases\Category\DeleteCategory\DeleteCategoryUseCase;
use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;

class DeleteCategoryUseCaseTest extends TestCase
{
    private CategoryRepositoryInterface $categoryRepository;
    private DeleteCategoryUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->useCase = new DeleteCategoryUseCase($this->categoryRepository);
    }

    public function test_it_deletes_category_successfully(): void
    {
        // Arrange
        $categoryId = CategoryId::generate();
        $category = Category::create(
            $categoryId,
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn($id) => $id->value() === $categoryId->value()))
            ->willReturn($category);

        $this->categoryRepository
            ->expects($this->once())
            ->method('countProducts')
            ->with($this->callback(fn($id) => $id->value() === $categoryId->value()))
            ->willReturn(0);

        $this->categoryRepository
            ->expects($this->once())
            ->method('delete')
            ->with($this->callback(fn($id) => $id->value() === $categoryId->value()));

        // Act
        $this->useCase->execute($categoryId->value());

        // Assert - no exception thrown
        $this->assertTrue(true);
    }

    public function test_it_throws_exception_when_category_not_found(): void
    {
        // Arrange
        $categoryId = CategoryId::generate()->value(); // Use valid UUID

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        // Assert
        $this->expectException(CategoryNotFoundException::class);
        $this->expectExceptionMessage("Category with ID {$categoryId} not found.");

        // Act
        $this->useCase->execute($categoryId);
    }

    public function test_it_throws_exception_when_category_has_products(): void
    {
        // Arrange
        $categoryId = CategoryId::generate();
        $category = Category::create(
            $categoryId,
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($category);

        $this->categoryRepository
            ->expects($this->once())
            ->method('countProducts')
            ->willReturn(5); // Has 5 products

        $this->categoryRepository
            ->expects($this->never())
            ->method('delete');

        // Assert
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot delete category with 5 associated products.');

        // Act
        $this->useCase->execute($categoryId->value());
    }
}
