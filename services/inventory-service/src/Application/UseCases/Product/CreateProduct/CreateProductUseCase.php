<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Product\CreateProduct;

use Src\Application\DTOs\ProductDTO;
use Src\Application\Exceptions\SKUAlreadyExistsException;
use Src\Domain\Entities\Product;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\ProductName;
use Src\Domain\ValueObjects\SKU;
use Src\Domain\ValueObjects\Price;
use Src\Domain\ValueObjects\CategoryId;

/**
 * Create Product Use Case
 * 
 * Caso de uso para criar um novo produto.
 */
final class CreateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(CreateProductDTO $dto): ProductDTO
    {
        // 1. Criar Value Objects
        $sku = SKU::fromString($dto->sku);
        
        // 2. Verificar se SKU já existe
        if ($this->productRepository->existsSKU($sku)) {
            throw SKUAlreadyExistsException::withSKU($dto->sku);
        }

        $productId = ProductId::generate();
        $name = ProductName::fromString($dto->name);
        $price = Price::fromFloat($dto->price);
        $categoryId = $dto->categoryId ? CategoryId::fromString($dto->categoryId) : null;

        // 3. Criar entidade Product
        $product = Product::create(
            id: $productId,
            name: $name,
            sku: $sku,
            price: $price,
            categoryId: $categoryId,
            barcode: $dto->barcode,
            description: $dto->description
        );

        // 4. Persistir
        $this->productRepository->save($product);

        // 5. Publicar eventos de domínio (será feito no repository)
        // $events = $product->pullDomainEvents();
        // $this->eventPublisher->publishAll($events);

        // 6. Retornar DTO
        return ProductDTO::fromEntity($product);
    }
}

