<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de conexão com o RabbitMQ.
    |
    */

    'host' => env('RABBITMQ_HOST', 'rabbitmq'),

    'port' => env('RABBITMQ_PORT', 5672),

    'user' => env('RABBITMQ_USER', 'admin'),

    'password' => env('RABBITMQ_PASSWORD', 'admin123'),

    'vhost' => env('RABBITMQ_VHOST', '/'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Configuration
    |--------------------------------------------------------------------------
    */

    'exchange' => [
        'auth' => env('RABBITMQ_EXCHANGE_AUTH', 'auth.events'),
    ],

];

