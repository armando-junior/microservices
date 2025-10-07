<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use Mockery;
use PHPUnit\Framework\TestCase;
use Src\Application\DTOs\Category\CreateCategoryInputDTO;
use Src\Application\UseCases\Category\CreateCategory\CreateCategoryUseCase;
use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;

class CreateCategoryUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_category_successfully(): void
    {
        // Arrange
        $repository = Mockery::mock(CategoryRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Category::class));

        $useCase = new CreateCategoryUseCase($repository);

        $input = new CreateCategoryInputDTO(
            name: 'Fornecedores',
            type: 'expense',
            description: 'Pagamentos a fornecedores'
        );

        // Act
        $output = $useCase->execute($input);

        // Assert
        $this->assertNotNull($output->id);
        $this->assertEquals('Fornecedores', $output->name);
        $this->assertEquals('expense', $output->type);
        $this->assertEquals('Pagamentos a fornecedores', $output->description);
    }

    public function test_it_creates_income_category(): void
    {
        // Arrange
        $repository = Mockery::mock(CategoryRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Category::class));

        $useCase = new CreateCategoryUseCase($repository);

        $input = new CreateCategoryInputDTO(
            name: 'Vendas',
            type: 'income'
        );

        // Act
        $output = $useCase->execute($input);

        // Assert
        $this->assertEquals('Vendas', $output->name);
        $this->assertEquals('income', $output->type);
        $this->assertNull($output->description);
    }
}


