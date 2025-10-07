<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\CreateSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\DTOs\Supplier\CreateSupplierInputDTO;
use Src\Application\DTOs\Supplier\UpdateSupplierInputDTO;
use Src\Application\UseCases\Supplier\CreateSupplier\CreateSupplierUseCase;
use Src\Application\UseCases\Supplier\GetSupplier\GetSupplierUseCase;
use Src\Application\UseCases\Supplier\ListSuppliers\ListSuppliersUseCase;
use Src\Application\UseCases\Supplier\UpdateSupplier\UpdateSupplierUseCase;

/**
 * SupplierController
 * 
 * Controller REST para gerenciamento de fornecedores.
 */
class SupplierController extends Controller
{
    public function __construct(
        private readonly CreateSupplierUseCase $createSupplierUseCase,
        private readonly UpdateSupplierUseCase $updateSupplierUseCase,
        private readonly GetSupplierUseCase $getSupplierUseCase,
        private readonly ListSuppliersUseCase $listSuppliersUseCase
    ) {
    }

    /**
     * Lista fornecedores com paginação
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);

        $result = $this->listSuppliersUseCase->execute($page, $perPage);

        return response()->json([
            'data' => SupplierResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
            ],
        ]);
    }

    /**
     * Busca um fornecedor por ID
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $supplier = $this->getSupplierUseCase->execute($id);

        return response()->json([
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Cria um novo fornecedor
     * 
     * @param CreateSupplierRequest $request
     * @return JsonResponse
     */
    public function store(CreateSupplierRequest $request): JsonResponse
    {
        $input = CreateSupplierInputDTO::fromArray($request->validated());
        $supplier = $this->createSupplierUseCase->execute($input);

        return response()->json([
            'data' => new SupplierResource($supplier),
            'message' => 'Supplier created successfully',
        ], 201);
    }

    /**
     * Atualiza um fornecedor
     * 
     * @param UpdateSupplierRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateSupplierRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $data['id'] = $id;

        $input = UpdateSupplierInputDTO::fromArray($data);
        $supplier = $this->updateSupplierUseCase->execute($input);

        return response()->json([
            'data' => new SupplierResource($supplier),
            'message' => 'Supplier updated successfully',
        ]);
    }
}


