<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Exceptions\UserNotFoundException;
use Src\Application\UseCases\GetUser\GetUserByIdUseCase;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

class UserController extends Controller
{
    public function __construct(
        private readonly GetUserByIdUseCase $getUserByIdUseCase,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Get user by ID.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            // Verificar se o usuário autenticado está tentando acessar seus próprios dados
            $authenticatedUserId = $request->attributes->get('user_id');
            
            if ($authenticatedUserId !== $id) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You can only access your own data',
                ], 403);
            }

            $user = $this->getUserByIdUseCase->execute($id);

            $userModel = \Src\Infrastructure\Persistence\Eloquent\Models\UserModel::find($id);

            return response()->json([
                'user' => new UserResource($userModel),
            ], 200);

        } catch (UserNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            // Verificar se o usuário autenticado está tentando atualizar seus próprios dados
            $authenticatedUserId = $request->attributes->get('user_id');
            
            if ($authenticatedUserId !== $id) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You can only update your own data',
                ], 403);
            }

            $userId = UserId::fromString($id);
            $user = $this->userRepository->findById($userId);

            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                    'message' => 'User not found',
                ], 404);
            }

            // Atualizar nome se fornecido
            if ($request->has('name')) {
                $newName = UserName::fromString($request->input('name'));
                $user->changeName($newName);
            }

            // Atualizar email se fornecido
            if ($request->has('email')) {
                $newEmail = Email::fromString($request->input('email'));
                $user->changeEmail($newEmail);
            }

            // Salvar alterações
            $this->userRepository->save($user);

            // Buscar modelo atualizado
            $userModel = \Src\Infrastructure\Persistence\Eloquent\Models\UserModel::find($id);

            return response()->json([
                'message' => 'User updated successfully',
                'user' => new UserResource($userModel),
            ], 200);

        } catch (UserNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete user (soft delete - mark as inactive).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            // Verificar se o usuário autenticado está tentando deletar sua própria conta
            $authenticatedUserId = $request->attributes->get('user_id');
            
            if ($authenticatedUserId !== $id) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You can only delete your own account',
                ], 403);
            }

            $userId = UserId::fromString($id);
            $user = $this->userRepository->findById($userId);

            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                    'message' => 'User not found',
                ], 404);
            }

            // Desativar usuário (soft delete)
            $user->deactivate();
            $this->userRepository->save($user);

            return response()->json([
                'message' => 'User deactivated successfully',
            ], 200);

        } catch (UserNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}

