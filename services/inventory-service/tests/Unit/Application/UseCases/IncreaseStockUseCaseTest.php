<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Mockery;
use Src\Application\UseCases\Stock\IncreaseStock\IncreaseStockUseCase;
use Src\Application\UseCases\Stock\IncreaseStock\IncreaseStockDTO;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\Entities\Stock;
use Src\Domain\ValueObjects\StockId;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;

class IncreaseStockUseCaseTest extends TestCase
{
    private StockRepositoryInterface $stockRepository;
    private IncreaseStockUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockRepository = Mockery::mock(StockRepositoryInterface::class);
        $this->useCase = new IncreaseStockUseCase($this->stockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_increases_stock_successfully(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';
        
        $dto = new IncreaseStockDTO(
            productId: $productId,
            quantity: 50,
            reason: 'Purchase order',
            referenceId: 'PO-001'
        );

        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::fromString($productId),
            initialQuantity: Quantity::fromInt(100),
            minimumQuantity: Quantity::fromInt(10)
        );

        $this->stockRepository
            ->shouldReceive('findByProductId')
            ->once()
            ->andReturn($stock);

        $this->stockRepository
            ->shouldReceive('save')
            ->once();

        $this->stockRepository
            ->shouldReceive('saveMovements')
            ->once();

        $result = $this->useCase->execute($dto);

        $this->assertEquals(150, $result->quantity);
    }

    public function test_it_throws_exception_when_stock_not_found(): void
    {
        $this->expectException(StockNotFoundException::class);

        $dto = new IncreaseStockDTO(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: 50,
            reason: 'Purchase order'
        );

        $this->stockRepository
            ->shouldReceive('findByProductId')
            ->once()
            ->andReturn(null);

        $this->useCase->execute($dto);
    }
}
