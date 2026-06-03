<?php
/**
 * Configuração central do projeto
 * Lê variáveis do ambiente ou de um arquivo .env
 * Nunca exponha credenciais reais neste arquivo
 */

function env(string $key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key) ?? $default;
    return $value;
}

// Carrega .env se existir (para desenvolvimento local)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

return [
    'tiny' => [
        'token'    => env('TINY_TOKEN'),
        'base_url' => 'https://api.tiny.com.br/api2',
    ],
    'soma' => [
        'bearer'   => env('SOMA_BEARER_TOKEN'),
        'base_url' => env('SOMA_BASE_URL', 'https://api.plataformasoma.com/v1/pedido/'),
    ],
    'vnda' => [
        'bearer'   => env('VNDA_BEARER_TOKEN'),
        'base_url' => env('VNDA_API_URL', 'https://api.vnda.com.br/api/v2/orders'),
    ],
    'db' => [
        'host' => env('DB_HOST', 'localhost'),
        'name' => env('DB_NAME'),
        'user' => env('DB_USER'),
        'pass' => env('DB_PASS'),
    ],
];
