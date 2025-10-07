<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
            \Log::info('Registration attempt started', ['email' => $request->input('email')]);
            
            $dto = new RegisterUserDTO(
                name: $request->input('name'),
                email: $request->input('email'),
                password: $request->input('password')
            );
            
            \Log::info('DTO created successfully');

            $authTokenDTO = $this->registerUserUseCase->execute($dto);
            
            \Log::info('Use case executed successfully', ['user_id' => $authTokenDTO->user->id]);

            return (new AuthTokenResource($authTokenDTO))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);

        } catch (EmailAlreadyExistsException $e) {
            \Log::warning('Registration failed: Email already exists', ['email' => $request->input('email')]);
            return response()->json([
                'error' => 'Email already exists',
                'message' => $e->getMessage(),
            ], 409);

        } catch (ApplicationException $e) {
            \Log::error('Registration failed: Application exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
            ], 400);
        }
        
        // Let other exceptions propagate to the global exception handler
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            \Log::info('Login attempt started', ['email' => $request->input('email')]);
            
            $dto = new LoginUserDTO(
                email: $request->input('email'),
                password: $request->input('password')
            );
            
            \Log::info('DTO created successfully');

            $authTokenDTO = $this->loginUserUseCase->execute($dto);
            
            \Log::info('Use case executed successfully', ['user_id' => $authTokenDTO->user->id]);

            return (new AuthTokenResource($authTokenDTO))
                ->response()
                ->setStatusCode(Response::HTTP_OK);

        } catch (InvalidCredentialsException $e) {
            \Log::warning('Login failed: Invalid credentials', ['email' => $request->input('email')]);
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => $e->getMessage(),
            ], 401);

        } catch (UserNotFoundException $e) {
            \Log::warning('Login failed: User not found', ['email' => $request->input('email')]);
            return response()->json([
                'error' => 'User not found',
                'message' => $e->getMessage(),
            ], 404);

        } catch (ApplicationException $e) {
            \Log::error('Login failed: Application exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
            ], 400);
        }
        
        // Let other exceptions propagate to the global exception handler
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Extrair o token do header Authorization
            $authHeader = $request->header('Authorization');
            
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'Authorization token not provided',
                ], 400);
            }
            
            $token = substr($authHeader, 7); // Remove "Bearer "

            $this->logoutUserUseCase->execute($token);

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
            $tokenGenerator = app(\Src\Application\Contracts\TokenGeneratorInterface::class);
            $newToken = $tokenGenerator->generate(
                \Src\Domain\ValueObjects\UserId::fromString($userId),
                [
                    'email' => $userDTO->email,
                    'name' => $userDTO->name,
                ]
            );

            // Criar AuthTokenDTO para a resposta
            $authTokenDTO = new \Src\Application\DTOs\AuthTokenDTO(
                accessToken: $newToken,
                tokenType: 'bearer',
                expiresIn: $tokenGenerator->getTTL(),
                user: $userDTO
            );

            return response()->json([
                'message' => 'Token refreshed successfully',
                'auth' => new AuthTokenResource($authTokenDTO),
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

