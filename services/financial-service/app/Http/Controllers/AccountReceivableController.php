<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccountReceivable\CreateAccountReceivableRequest;
use App\Http\Requests\AccountReceivable\ReceiveAccountReceivableRequest;
use App\Http\Resources\AccountReceivableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\DTOs\AccountReceivable\CreateAccountReceivableInputDTO;
use Src\Application\DTOs\AccountReceivable\ReceiveAccountReceivableInputDTO;
use Src\Application\UseCases\AccountReceivable\CreateAccountReceivable\CreateAccountReceivableUseCase;
use Src\Application\UseCases\AccountReceivable\ListAccountsReceivable\ListAccountsReceivableUseCase;
use Src\Application\UseCases\AccountReceivable\ReceiveAccountReceivable\ReceiveAccountReceivableUseCase;

/**
 * AccountReceivableController
 * 
 * Controller REST para gerenciamento de contas a receber.
 */
class AccountReceivableController extends Controller
{
    public function __construct(
        private readonly CreateAccountReceivableUseCase $createAccountReceivableUseCase,
        private readonly ReceiveAccountReceivableUseCase $receiveAccountReceivableUseCase,
        private readonly ListAccountsReceivableUseCase $listAccountsReceivableUseCase
    ) {
    }

    /**
     * Lista contas a receber com paginação e filtros
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);

        $filters = $request->only(['status', 'customer_id', 'due_date_from', 'due_date_to']);

        $result = $this->listAccountsReceivableUseCase->execute($page, $perPage, $filters);

        return response()->json([
            'data' => AccountReceivableResource::collection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
            ],
        ]);
    }

    /**
     * Cria uma nova conta a receber
     * 
     * @param CreateAccountReceivableRequest $request
     * @return JsonResponse
     */
    public function store(CreateAccountReceivableRequest $request): JsonResponse
    {
        $input = CreateAccountReceivableInputDTO::fromArray($request->validated());
        $account = $this->createAccountReceivableUseCase->execute($input);

        return response()->json([
            'data' => new AccountReceivableResource($account),
            'message' => 'Account receivable created successfully',
        ], 201);
    }

    /**
     * Registra o recebimento de uma conta
     * 
     * @param ReceiveAccountReceivableRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function receive(ReceiveAccountReceivableRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $data['account_receivable_id'] = $id;

        $input = ReceiveAccountReceivableInputDTO::fromArray($data);
        $account = $this->receiveAccountReceivableUseCase->execute($input);

        return response()->json([
            'data' => new AccountReceivableResource($account),
            'message' => 'Account receivable received successfully',
        ]);
    }
}


