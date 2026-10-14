<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | JWT del laboratorio (Fase 8)
    |--------------------------------------------------------------------------
    |
    | HS256 con secreto dedicado (no APP_KEY): los tokens del experimento
    | deben invalidarse sin rotar la clave de la aplicación.
    |
    */

    'secret' => env('JWT_SECRET', ''),
    'ttl' => (int) env('JWT_TTL', 3600),
    'algo' => env('JWT_ALGO', 'HS256'),
];
