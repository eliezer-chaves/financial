<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Rotas que permitirão requisições de outras origens
    'paths' => [
        'api/*',                  // Todas as rotas da API
        'sanctum/csrf-cookie',    // Rota para obter o token CSRF
        'login',                  // Rota de login
        'logout'                  // Rota de logout
    ],

    // Quais origens (domínios) podem acessar sua API
    'allowed_origins' => [
        env('SPA_URL', env('FRONT_URL') ),  // URL do seu frontend
    ],

    // Quais métodos HTTP são permitidos
    'allowed_methods' => ['*'],  // GET, POST, PUT, DELETE, etc.

    // Quais headers podem ser enviados nas requisições
    'allowed_headers' => ['*'],  // Content-Type, Authorization, etc.

    // Headers que o navegador pode ler na resposta
    'exposed_headers' => [],

    // Tempo de cache da configuração CORS (em segundos)
    'max_age' => 0,

    // CRÍTICO: Permite envio de cookies e credenciais
    'supports_credentials' => true,  // Necessário para HTTP-only cookies
];
