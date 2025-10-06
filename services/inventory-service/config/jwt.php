<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | This key is used to sign and verify JWT tokens. It must match the
    | key used by the Auth Service.
    |
    */
    'secret' => env('JWT_SECRET', 'your-secret-key'),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign the token. Default is HS256.
    |
    */
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | JWT TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | The time (in minutes) that the token will be valid for.
    |
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh TTL
    |--------------------------------------------------------------------------
    |
    | The time (in minutes) that the refresh token will be valid for.
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 weeks
];