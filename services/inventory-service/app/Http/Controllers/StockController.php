<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StockOperationRequest;
use App\Http\Resources\StockResource;
use Illuminate\Http\JsonResponse;
use Src\Application\UseCases\Stock\GetStock\GetStockUseCase;
use Src\Application\UseCases\Stock\IncreaseStock\IncreaseStockDTO;
use Src\Application\UseCases\Stock\IncreaseStock\IncreaseStockUseCase;
use Src\Application\UseCases\Stock\DecreaseStock\DecreaseStockDTO;
use Src\Application\UseCases\Stock\DecreaseStock\DecreaseStockUseCase;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Domain\Exceptions\InsufficientStockException;

/**
 * Stock Controller
 */
class StockController extends Controller
{
    /**
     * Busca estoque de um produto
     */
    public function show(string $productId, GetStockUseCase $useCase): JsonResponse
    {
        try {
            $stock = $useCase->execute($productId);

            return response()->json([
                'data' => new StockResource($stock),
            ]);
        } catch (StockNotFoundException $e) {
            return response()->json([
                'error' => 'StockNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Aumenta estoque (entrada)
     */
    public function increase(string $productId, StockOperationRequest $request, IncreaseStockUseCase $useCase): JsonResponse
    {
        try {
            $dto = new IncreaseStockDTO(
                productId: $productId,
                quantity: (int) $request->input('quantity'),
                reason: $request->input('reason'),
                referenceId: $request->input('reference_id')
            );

            $stock = $useCase->execute($dto);

            return response()->json([
                'message' => 'Stock increased successfully',
                'data' => new StockResource($stock),
            ]);
        } catch (StockNotFoundException $e) {
            return response()->json([
                'error' => 'StockNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Diminui estoque (saída)
     */
    public function decrease(string $productId, StockOperationRequest $request, DecreaseStockUseCase $useCase): JsonResponse
    {
        try {
            $dto = new DecreaseStockDTO(
                productId: $productId,
                quantity: (int) $request->input('quantity'),
                reason: $request->input('reason'),
                referenceId: $request->input('reference_id')
            );

            $stock = $useCase->execute($dto);

            return response()->json([
                'message' => 'Stock decreased successfully',
                'data' => new StockResource($stock),
            ]);
        } catch (StockNotFoundException $e) {
            return response()->json([
                'error' => 'StockNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'error' => 'InsufficientStock',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Lista produtos com estoque baixo
     */
    public function lowStock(): JsonResponse
    {
        // TODO: Implementar GetLowStockUseCase
        return response()->json([
            'message' => 'Low stock endpoint - To be implemented',
        ], 501);
    }

    /**
     * Lista produtos esgotados
     */
    public function depleted(): JsonResponse
    {
        // TODO: Implementar GetDepletedStockUseCase
        return response()->json([
            'message' => 'Depleted stock endpoint - To be implemented',
        ], 501);
    }
}

