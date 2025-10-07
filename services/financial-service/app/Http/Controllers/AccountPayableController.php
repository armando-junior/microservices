<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccountPayable\CreateAccountPayableRequest;
use App\Http\Requests\AccountPayable\PayAccountPayableRequest;
use App\Http\Resources\AccountPayableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\DTOs\AccountPayable\CreateAccountPayableInputDTO;
use Src\Application\DTOs\AccountPayable\PayAccountPayableInputDTO;
use Src\Application\UseCases\AccountPayable\CreateAccountPayable\CreateAccountPayableUseCase;
use Src\Application\UseCases\AccountPayable\ListAccountsPayable\ListAccountsPayableUseCase;
use Src\Application\UseCases\AccountPayable\PayAccountPayable\PayAccountPayableUseCase;

/**
 * AccountPayableController
 * 
 * Controller REST para gerenciamento de contas a pagar.
 */
class AccountPayableController extends Controller
{
    public function __construct(
        private readonly CreateAccountPayableUseCase $createAccountPayableUseCase,
        private readonly PayAccountPayableUseCase $payAccountPayableUseCase,
        private readonly ListAccountsPayableUseCase $listAccountsPayableUseCase
    ) {
    }

    /**
     * Lista contas a pagar com paginação e filtros
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);

        $filters = $request->only(['status', 'supplier_id', 'due_date_from', 'due_date_to']);

        $result = $this->listAccountsPayableUseCase->execute($page, $perPage, $filters);

        return response()->json([
            'data' => AccountPayableResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
            ],
        ]);
    }

    /**
     * Cria uma nova conta a pagar
     * 
     * @param CreateAccountPayableRequest $request
     * @return JsonResponse
     */
    public function store(CreateAccountPayableRequest $request): JsonResponse
    {
        $input = CreateAccountPayableInputDTO::fromArray($request->validated());
        $account = $this->createAccountPayableUseCase->execute($input);

        return response()->json([
            'data' => new AccountPayableResource($account),
            'message' => 'Account payable created successfully',
        ], 201);
    }

    /**
     * Registra o pagamento de uma conta
     * 
     * @param PayAccountPayableRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function pay(PayAccountPayableRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $data['account_payable_id'] = $id;

        $input = PayAccountPayableInputDTO::fromArray($data);
        $account = $this->payAccountPayableUseCase->execute($input);

        return response()->json([
            'data' => new AccountPayableResource($account),
            'message' => 'Account payable paid successfully',
        ]);
    }
}


