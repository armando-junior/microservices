<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\DTOs\Category\CreateCategoryInputDTO;
use Src\Application\DTOs\Category\UpdateCategoryInputDTO;
use Src\Application\UseCases\Category\CreateCategory\CreateCategoryUseCase;
use Src\Application\UseCases\Category\ListCategories\ListCategoriesUseCase;
use Src\Application\UseCases\Category\UpdateCategory\UpdateCategoryUseCase;

/**
 * CategoryController
 * 
 * Controller REST para gerenciamento de categorias financeiras.
 */
class CategoryController extends Controller
{
    public function __construct(
        private readonly CreateCategoryUseCase $createCategoryUseCase,
        private readonly UpdateCategoryUseCase $updateCategoryUseCase,
        private readonly ListCategoriesUseCase $listCategoriesUseCase
    ) {
    }

    /**
     * Lista categorias (com filtro opcional por tipo)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type'); // 'income' ou 'expense'

        $categories = $this->listCategoriesUseCase->execute($type);

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Cria uma nova categoria
     * 
     * @param CreateCategoryRequest $request
     * @return JsonResponse
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $input = CreateCategoryInputDTO::fromArray($request->validated());
        $category = $this->createCategoryUseCase->execute($input);

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Category created successfully',
        ], 201);
    }

    /**
     * Atualiza uma categoria
     * 
     * @param UpdateCategoryRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $data['id'] = $id;

        $input = UpdateCategoryInputDTO::fromArray($data);
        $category = $this->updateCategoryUseCase->execute($input);

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Category updated successfully',
        ]);
    }
}


