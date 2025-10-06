<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Src\Application\UseCases\Order\CreateOrder\CreateOrderDTO;
use Src\Application\UseCases\Order\CreateOrder\CreateOrderUseCase;
use Src\Application\UseCases\Order\GetOrder\GetOrderUseCase;
use Src\Application\Exceptions\CustomerNotFoundException;
use Src\Application\Exceptions\OrderNotFoundException;

/**
 * Order Controller
 */
class OrderController extends Controller
{
    /**
     * Cria um novo pedido (draft)
     */
    public function store(CreateOrderRequest $request, CreateOrderUseCase $useCase): JsonResponse
    {
        try {
            $dto = new CreateOrderDTO(
                customerId: $request->input('customer_id'),
                notes: $request->input('notes')
            );

            $order = $useCase->execute($dto);

            return response()->json([
                'message' => 'Order created successfully',
                'data' => new OrderResource($order),
            ], 201);
        } catch (CustomerNotFoundException $e) {
            return response()->json([
                'error' => 'CustomerNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Busca um pedido
     */
    public function show(string $id, GetOrderUseCase $useCase): JsonResponse
    {
        try {
            $order = $useCase->execute($id);

            return response()->json([
                'data' => new OrderResource($order),
            ]);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'error' => 'OrderNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
