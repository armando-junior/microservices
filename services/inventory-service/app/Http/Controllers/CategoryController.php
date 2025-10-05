<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Src\Application\UseCases\Category\CreateCategory\CreateCategoryDTO;
use Src\Application\UseCases\Category\CreateCategory\CreateCategoryUseCase;
use Src\Application\UseCases\Category\GetCategory\GetCategoryUseCase;
use Src\Application\Exceptions\CategoryNotFoundException;

/**
 * Category Controller
 */
class CategoryController extends Controller
{
    /**
     * Lista categorias
     */
    public function index(): JsonResponse
    {
        // TODO: Implementar ListCategoriesUseCase
        return response()->json([
            'message' => 'List categories endpoint - To be implemented',
        ], 501);
    }

    /**
     * Busca uma categoria
     */
    public function show(string $id, GetCategoryUseCase $useCase): JsonResponse
    {
        try {
            $category = $useCase->execute($id);

            return response()->json([
                'data' => new CategoryResource($category),
            ]);
        } catch (CategoryNotFoundException $e) {
            return response()->json([
                'error' => 'CategoryNotFound',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Cria uma categoria
     */
    public function store(CreateCategoryRequest $request, CreateCategoryUseCase $useCase): JsonResponse
    {
        $dto = new CreateCategoryDTO(
            name: $request->input('name'),
            description: $request->input('description')
        );

        $category = $useCase->execute($dto);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Atualiza uma categoria
     */
    public function update(string $id): JsonResponse
    {
        // TODO: Implementar UpdateCategoryUseCase
        return response()->json([
            'message' => 'Update category endpoint - To be implemented',
        ], 501);
    }

    /**
     * Deleta uma categoria
     */
    public function destroy(string $id): JsonResponse
    {
        // TODO: Implementar DeleteCategoryUseCase
        return response()->json([
            'message' => 'Delete category endpoint - To be implemented',
        ], 501);
    }
}

