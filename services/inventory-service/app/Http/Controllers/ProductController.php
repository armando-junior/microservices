<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\UseCases\Product\CreateProduct\CreateProductDTO;
use Src\Application\UseCases\Product\CreateProduct\CreateProductUseCase;
use Src\Application\UseCases\Product\GetProduct\GetProductUseCase;
use Src\Application\UseCases\Product\ListProducts\ListProductsUseCase;
use Src\Application\UseCases\Product\UpdateProduct\UpdateProductDTO;
use Src\Application\UseCases\Product\UpdateProduct\UpdateProductUseCase;
use Src\Application\UseCases\Product\DeleteProduct\DeleteProductUseCase;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Application\Exceptions\ProductNotFoundException;
use Src\Application\Exceptions\SKUAlreadyExistsException;

/**
 * Product Controller
 */
class ProductController extends Controller
{
    /**
     * Lista produtos
     */
    public function index(Request $request, ListProductsUseCase $useCase): JsonResponse
    {
        $products = $useCase->execute(
            status: $request->query('status'),
            categoryId: $request->query('category_id'),
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 15)
        );

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'page' => (int) $request->query('page', 1),
                'per_page' => (int) $request->query('per_page', 15),
            ]
        ]);
    }

    /**
     * Busca um produto
     */
    public function show(string $id, GetProductUseCase $useCase): JsonResponse
    {
        try {
            $product = $useCase->execute($id);

            return response()->json([
                'data' => new ProductResource($product),
            ]);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'error' => 'ProductNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Cria um produto
     */
    public function store(CreateProductRequest $request, CreateProductUseCase $useCase): JsonResponse
    {
        try {
            $dto = new CreateProductDTO(
                name: $request->input('name'),
                sku: $request->input('sku'),
                price: (float) $request->input('price'),
                categoryId: $request->input('category_id'),
                barcode: $request->input('barcode'),
                description: $request->input('description')
            );

            $product = $useCase->execute($dto);

            return response()->json([
                'message' => 'Product created successfully',
                'data' => new ProductResource($product),
            ], 201);
        } catch (SKUAlreadyExistsException $e) {
            return response()->json([
                'error' => 'SKUAlreadyExists',
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Atualiza um produto
     */
    public function update(string $id, UpdateProductRequest $request, UpdateProductUseCase $useCase): JsonResponse
    {
        try {
            $dto = new UpdateProductDTO(
                id: $id,
                name: $request->input('name'),
                price: $request->has('price') ? (float) $request->input('price') : null,
                categoryId: $request->input('category_id'),
                barcode: $request->input('barcode'),
                description: $request->input('description'),
                status: $request->input('status')
            );

            $product = $useCase->execute($dto);

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => new ProductResource($product),
            ]);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'error' => 'ProductNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (CategoryNotFoundException $e) {
            return response()->json([
                'error' => 'CategoryNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Deleta um produto
     */
    public function destroy(string $id, DeleteProductUseCase $useCase): JsonResponse
    {
        try {
            $useCase->execute($id);

            return response()->json([
                'message' => 'Product deleted successfully',
            ], 200);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'error' => 'ProductNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => 'ProductHasStock',
                'message' => $e->getMessage(),
            ], 409);
        }
    }
}

