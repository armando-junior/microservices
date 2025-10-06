<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Src\Application\UseCases\Category\ListCategories\ListCategoriesUseCase;
use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;

class ListCategoriesUseCaseTest extends TestCase
{
    private CategoryRepositoryInterface $categoryRepository;
    private ListCategoriesUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->useCase = new ListCategoriesUseCase($this->categoryRepository);
    }

    public function test_it_lists_all_categories(): void
    {
        // Arrange
        $category1 = Category::create(
            CategoryId::generate(),
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $category2 = Category::create(
            CategoryId::generate(),
            CategoryName::fromString('Books'),
            'Books and publications'
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('list')
            ->with([])
            ->willReturn([$category1, $category2]);

        // Act
        $result = $this->useCase->execute([]);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Electronics', $result[0]->name);
        $this->assertEquals('Books', $result[1]->name);
    }

    public function test_it_lists_categories_with_status_filter(): void
    {
        // Arrange
        $category = Category::create(
            CategoryId::generate(),
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $filters = ['status' => 'active'];

        $this->categoryRepository
            ->expects($this->once())
            ->method('list')
            ->with($filters)
            ->willReturn([$category]);

        // Act
        $result = $this->useCase->execute($filters);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('active', $result[0]->status);
    }

    public function test_it_returns_empty_array_when_no_categories(): void
    {
        // Arrange
        $this->categoryRepository
            ->expects($this->once())
            ->method('list')
            ->with([])
            ->willReturn([]);

        // Act
        $result = $this->useCase->execute([]);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }
}
