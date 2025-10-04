<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\DTOs\LoginUserDTO;
use Src\Application\DTOs\RegisterUserDTO;
use Src\Application\Exceptions\ApplicationException;
use Src\Application\Exceptions\EmailAlreadyExistsException;
use Src\Application\Exceptions\InvalidCredentialsException;
use Src\Application\Exceptions\UserNotFoundException;
use Src\Application\UseCases\GetUser\GetUserByIdUseCase;
use Src\Application\UseCases\LoginUser\LoginUserUseCase;
use Src\Application\UseCases\LogoutUser\LogoutUserUseCase;
use Src\Application\UseCases\RegisterUser\RegisterUserUseCase;
use Src\Infrastructure\Persistence\Eloquent\Models\UserModel;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly LoginUserUseCase $loginUserUseCase,
        private readonly LogoutUserUseCase $logoutUserUseCase,
        private readonly GetUserByIdUseCase $getUserByIdUseCase,
    ) {
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $dto = new RegisterUserDTO(
                name: $request->input('name'),
                email: $request->input('email'),
                password: $request->input('password')
            );

            $result = $this->registerUserUseCase->execute($dto);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => new UserResource($this->getUserModel($result->userId)),
                'auth' => new AuthTokenResource($result->authToken),
            ], 201);

        } catch (EmailAlreadyExistsException $e) {
            return response()->json([
                'error' => 'Email already exists',
                'message' => $e->getMessage(),
            ], 409);

        } catch (ApplicationException $e) {
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $dto = new LoginUserDTO(
                email: $request->input('email'),
                password: $request->input('password')
            );

            $result = $this->loginUserUseCase->execute($dto);

            return response()->json([
                'message' => 'Login successful',
                'user' => new UserResource($this->getUserModel($result->userId)),
                'auth' => new AuthTokenResource($result->authToken),
            ], 200);

        } catch (InvalidCredentialsException $e) {
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => $e->getMessage(),
            ], 401);

        } catch (UserNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'message' => $e->getMessage(),
            ], 404);

        } catch (ApplicationException $e) {
            return response()->json([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $tokenJti = $request->attributes->get('token_jti');

            if (!$tokenJti) {
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'Token JTI not found',
                ], 400);
            }

            $this->logoutUserUseCase->execute($tokenJti);

            return response()->json([
                'message' => 'Logout successful',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Logout failed',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('user_id');

            if (!$userId) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'User ID not found in token',
                ], 401);
            }

            $user = $this->getUserByIdUseCase->execute($userId);

            return response()->json([
                'user' => new UserResource($this->getUserModel($user->id)),
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
     * Refresh access token.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('user_id');

            if (!$userId) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'User ID not found in token',
                ], 401);
            }

            // Fazer logout do token atual
            $tokenJti = $request->attributes->get('token_jti');
            if ($tokenJti) {
                $this->logoutUserUseCase->execute($tokenJti);
            }

            // Buscar usuário
            $userDTO = $this->getUserByIdUseCase->execute($userId);
            $user = UserModel::find($userId);

            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                    'message' => 'User not found in database',
                ], 404);
            }

            // Gerar novo token
            $dto = new LoginUserDTO(
                email: $userDTO->email,
                password: '' // Não precisa da senha para refresh
            );

            // Para refresh, vamos apenas gerar um novo token sem validar senha
            $tokenGenerator = app(\Src\Application\Contracts\TokenGeneratorInterface::class);
            $newToken = $tokenGenerator->generate($userId, $userDTO->email);

            return response()->json([
                'message' => 'Token refreshed successfully',
                'auth' => new AuthTokenResource($newToken),
            ], 200);

        } catch (UserNotFoundException $e) {
            return response()->json([
                'error' => 'User not found',
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Refresh failed',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Get user model for resource.
     */
    private function getUserModel(string $userId): UserModel
    {
        return UserModel::findOrFail($userId);
    }
}

