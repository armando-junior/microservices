<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCustomerRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\UseCases\Customer\CreateCustomer\CreateCustomerDTO;
use Src\Application\UseCases\Customer\CreateCustomer\CreateCustomerUseCase;
use Src\Application\UseCases\Customer\GetCustomer\GetCustomerUseCase;
use Src\Application\UseCases\Customer\ListCustomers\ListCustomersUseCase;
use Src\Application\Exceptions\CustomerNotFoundException;
use Src\Application\Exceptions\EmailAlreadyExistsException;
use Src\Application\Exceptions\DocumentAlreadyExistsException;

/**
 * Customer Controller
 */
class CustomerController extends Controller
{
    /**
     * Lista clientes
     */
    public function index(Request $request, ListCustomersUseCase $useCase): JsonResponse
    {
        $filters = [];
        if ($request->has('status')) {
            $filters['status'] = $request->input('status');
        }
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        $customers = $useCase->execute($filters, $page, $perPage);
        $total = $useCase->count($filters);

        return response()->json([
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Cria um novo cliente
     */
    public function store(CreateCustomerRequest $request, CreateCustomerUseCase $useCase): JsonResponse
    {
        try {
            $dto = new CreateCustomerDTO(
                name: $request->input('name'),
                email: $request->input('email'),
                phone: $request->input('phone'),
                document: $request->input('document'),
                addressStreet: $request->input('address_street'),
                addressNumber: $request->input('address_number'),
                addressComplement: $request->input('address_complement'),
                addressCity: $request->input('address_city'),
                addressState: $request->input('address_state'),
                addressZipCode: $request->input('address_zip_code')
            );

            $customer = $useCase->execute($dto);

            return response()->json([
                'message' => 'Customer created successfully',
                'data' => new CustomerResource($customer),
            ], 201);
        } catch (EmailAlreadyExistsException $e) {
            return response()->json([
                'error' => 'EmailAlreadyExists',
                'message' => $e->getMessage(),
            ], 409);
        } catch (DocumentAlreadyExistsException $e) {
            return response()->json([
                'error' => 'DocumentAlreadyExists',
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Busca um cliente
     */
    public function show(string $id, GetCustomerUseCase $useCase): JsonResponse
    {
        try {
            $customer = $useCase->execute($id);

            return response()->json([
                'data' => new CustomerResource($customer),
            ]);
        } catch (CustomerNotFoundException $e) {
            return response()->json([
                'error' => 'CustomerNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
