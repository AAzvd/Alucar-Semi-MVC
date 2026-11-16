<?php

declare(strict_types=1);

/**
 * Configuração do projeto. Os valores padrão servem para XAMPP/Laragon;
 * para mudar sem editar o código, use variáveis de ambiente (ALUCAR_*).
 */

$env = static function (string $nome, string $padrao): string {
    $valor = getenv($nome);
    return $valor === false ? $padrao : $valor;
};

return [
    'db' => [
        'driver'  => $env('ALUCAR_DB_DRIVER', 'mysql'),   // 'mysql' (padrão) ou 'sqlite' (usado nos testes)
        'host'    => $env('ALUCAR_DB_HOST', 'localhost'),
        'porta'   => $env('ALUCAR_DB_PORT', '3306'),
        'nome'    => $env('ALUCAR_DB_NAME', 'alucar'),
        'usuario' => $env('ALUCAR_DB_USER', 'root'),
        'senha'   => $env('ALUCAR_DB_PASS', ''),
        'arquivo' => $env('ALUCAR_DB_FILE', ':memory:'),  // só para sqlite
    ],

    // Só preencha se a detecção automática da pasta do projeto falhar.
    'base_url' => getenv('ALUCAR_BASE_URL') !== false ? getenv('ALUCAR_BASE_URL') : null,
];
