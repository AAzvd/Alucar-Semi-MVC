<?php

declare(strict_types=1);


define('RAIZ', dirname(__DIR__));

// Autoload PSR-4 simples: a classe App\Models\Carro está em app/Models/Carro.php.
// Assim não precisamos de dezenas de require_once (nem do Composer).
spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }
    $arquivo = __DIR__ . '/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});

require_once __DIR__ . '/helpers.php';

// Qualquer erro que ninguém tratou vira uma página genérica (e o detalhe vai pro log).
// Evita vazar senha/host do banco na tela, como acontecia com o die($e->getMessage()).
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        return;
    }
    http_response_code(500);
    echo 'Ocorreu um erro interno. Tente novamente em instantes.';
});

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,       // JavaScript não lê o cookie de sessão
        'samesite' => 'Lax',      // reforça a proteção contra CSRF
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}
