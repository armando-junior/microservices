<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\UseCases\Category\CreateCategory\CreateCategoryDTO;
use Src\Application\UseCases\Category\CreateCategory\CreateCategoryUseCase;
use Src\Application\UseCases\Category\GetCategory\GetCategoryUseCase;
use Src\Application\UseCases\Category\ListCategories\ListCategoriesUseCase;
use Src\Application\UseCases\Category\UpdateCategory\UpdateCategoryDTO;
use Src\Application\UseCases\Category\UpdateCategory\UpdateCategoryUseCase;
use Src\Application\UseCases\Category\DeleteCategory\DeleteCategoryUseCase;
use Src\Application\Exceptions\CategoryNotFoundException;

/**
 * Category Controller
 */
class CategoryController extends Controller
{
    /**
     * Lista categorias
     */
    public function index(Request $request, ListCategoriesUseCase $useCase): JsonResponse
    {
        $categories = $useCase->execute([
            'status' => $request->query('status'),
        ]);

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
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
    public function update(string $id, UpdateCategoryRequest $request, UpdateCategoryUseCase $useCase): JsonResponse
    {
        try {
            $dto = new UpdateCategoryDTO(
                id: $id,
                name: $request->input('name'),
                description: $request->input('description'),
                status: $request->input('status')
            );

            $category = $useCase->execute($dto);

            return response()->json([
                'message' => 'Category updated successfully',
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
     * Deleta uma categoria
     */
    public function destroy(string $id, DeleteCategoryUseCase $useCase): JsonResponse
    {
        try {
            $useCase->execute($id);

            return response()->json([
                'message' => 'Category deleted successfully',
            ], 200);
        } catch (CategoryNotFoundException $e) {
            return response()->json([
                'error' => 'CategoryNotFound',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => 'CategoryHasProducts',
                'message' => $e->getMessage(),
            ], 409);
        }
    }
}

