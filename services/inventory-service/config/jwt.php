<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | Chave secreta usada para assinar os tokens JWT.
    | IMPORTANTE: Gere uma chave forte em produção!
    |
    */

    'secret' => env('JWT_SECRET', 'your-secret-key-change-this-in-production'),

    /*
    |--------------------------------------------------------------------------
    | JWT Time to Live (TTL)
    |--------------------------------------------------------------------------
    |
    | Tempo de vida do token em segundos.
    | Padrão: 3600 segundos (1 hora)
    |
    */

    'ttl' => env('JWT_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | JWT Issuer
    |--------------------------------------------------------------------------
    |
    | Identificador do emissor do token.
    |
    */

    'issuer' => env('JWT_ISSUER', 'auth-service'),

];

