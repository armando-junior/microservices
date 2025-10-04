<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'error' => 'Token not provided',
                'message' => 'Authorization token is required',
            ], 401);
        }

        try {
            // Verificar se o token está na blacklist
            if ($this->isTokenBlacklisted($token)) {
                return response()->json([
                    'error' => 'Token revoked',
                    'message' => 'This token has been revoked',
                ], 401);
            }

            // Decodificar e validar o token
            $decoded = JWT::decode(
                $token,
                new Key(config('jwt.secret'), config('jwt.algo', 'HS256'))
            );

            // Adicionar dados do usuário ao request
            $request->attributes->set('user_id', $decoded->sub);
            $request->attributes->set('user_email', $decoded->email ?? null);
            $request->attributes->set('token_jti', $decoded->jti);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Invalid token',
                'message' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }

    /**
     * Extract token from request header.
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    /**
     * Check if token is blacklisted.
     */
    private function isTokenBlacklisted(string $token): bool
    {
        try {
            $decoded = JWT::decode(
                $token,
                new Key(config('jwt.secret'), config('jwt.algo', 'HS256'))
            );

            $jti = $decoded->jti ?? null;
            
            if (!$jti) {
                return false;
            }

            return Cache::has("jwt:blacklist:{$jti}");
            
        } catch (Exception $e) {
            return false;
        }
    }
}

