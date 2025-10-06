<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\UseCases\Order\CreateOrder\CreateOrderDTO;
use Src\Application\UseCases\Order\CreateOrder\CreateOrderUseCase;
use Src\Application\UseCases\Order\GetOrder\GetOrderUseCase;
use Src\Application\UseCases\Order\AddOrderItem\AddOrderItemDTO;
use Src\Application\UseCases\Order\AddOrderItem\AddOrderItemUseCase;
use Src\Application\UseCases\Order\ConfirmOrder\ConfirmOrderUseCase;
use Src\Application\UseCases\Order\CancelOrder\CancelOrderDTO;
use Src\Application\UseCases\Order\CancelOrder\CancelOrderUseCase;
use Src\Application\UseCases\Order\ListOrders\ListOrdersUseCase;
use Src\Application\Exceptions\CustomerNotFoundException;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Application\Exceptions\ProductNotFoundException;

/**
 * Order Controller
 */
class OrderController extends Controller
{
    /**
     * Lista pedidos
     */
    public function index(Request $request, ListOrdersUseCase $useCase): JsonResponse
    {
        $filters = [];
        if ($request->has('status')) {
            $filters['status'] = $request->input('status');
        }
        if ($request->has('payment_status')) {
            $filters['payment_status'] = $request->input('payment_status');
        }
        if ($request->has('customer_id')) {
            $filters['customer_id'] = $request->input('customer_id');
        }

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        $orders = $useCase->execute($filters, $page, $perPage);
        $total = $useCase->count($filters);

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

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

    /**
     * Adiciona um item ao pedido
     */
    public function addItem(Request $request, string $id, AddOrderItemUseCase $useCase): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string|uuid',
            'quantity' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
        ]);

        try {
            $dto = new AddOrderItemDTO(
                orderId: $id,
                productId: $request->input('product_id'),
                quantity: $request->integer('quantity'),
                discount: $request->input('discount') ? (float) $request->input('discount') : null
            );

            $order = $useCase->execute($dto);

            return response()->json([
                'message' => 'Item added to order successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'error' => 'OrderNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'error' => 'ProductNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => 'DomainError',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirma o pedido
     */
    public function confirm(string $id, ConfirmOrderUseCase $useCase): JsonResponse
    {
        try {
            $order = $useCase->execute($id);

            return response()->json([
                'message' => 'Order confirmed successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'error' => 'OrderNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => 'DomainError',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancela o pedido
     */
    public function cancel(Request $request, string $id, CancelOrderUseCase $useCase): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $dto = new CancelOrderDTO(
                orderId: $id,
                reason: $request->input('reason')
            );

            $order = $useCase->execute($dto);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'error' => 'OrderNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => 'DomainError',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
