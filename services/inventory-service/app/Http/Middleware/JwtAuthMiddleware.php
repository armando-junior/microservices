<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token not provided',
            ], 401);
        }

        try {
            $secret = config('jwt.secret');
            $algorithm = config('jwt.algorithm', 'HS256');

            $decoded = JWT::decode($token, new Key($secret, $algorithm));

            // Adicionar informações do usuário ao request
            $request->attributes->set('user_id', $decoded->sub);
            $request->attributes->set('user_email', $decoded->email ?? null);
            $request->attributes->set('user_name', $decoded->name ?? null);

            return $next($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'error' => 'TokenExpired',
                'message' => 'Token has expired',
            ], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json([
                'error' => 'InvalidToken',
                'message' => 'Token signature is invalid',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'InvalidToken',
                'message' => 'Token is invalid: ' . $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Extract token from request
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // Try Authorization header first
        $header = $request->header('Authorization');
        
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        // Try token parameter
        return $request->input('token');
    }
}