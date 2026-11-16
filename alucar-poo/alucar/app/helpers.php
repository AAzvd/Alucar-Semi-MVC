<?php

declare(strict_types=1);

/** Funções pequenas usadas em todo o projeto (principalmente nas views). */

/** Lê config/config.php com "notação de ponto": config('db.host'). */
function config(string $chave, mixed $padrao = null): mixed
{
    static $dados = null;
    $dados ??= require RAIZ . '/config/config.php';

    $valor = $dados;
    foreach (explode('.', $chave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $padrao;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/** Escapa texto antes de imprimir em HTML (proteção contra XSS). */
function e(mixed $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Caminho do projeto no servidor. Em http://localhost/alucar/ devolve "/alucar";
 * com `php -S` na pasta do projeto devolve "".
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configurada = config('base_url');
    if (is_string($configurada)) {
        return $base = rtrim($configurada, '/');
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $raiz = realpath(RAIZ);
    $base = ($docRoot && $raiz && str_starts_with($raiz, $docRoot))
        ? str_replace('\\', '/', substr($raiz, strlen($docRoot)))
        : '';

    return $base = rtrim($base, '/');
}

/** url('assets/css/admin.css') -> "/alucar/assets/css/admin.css" */
function url(string $caminho = ''): string
{
    return base_url() . '/' . ltrim($caminho, '/');
}

function redirecionar(string $caminho): never
{
    header('Location: ' . url($caminho));
    exit;
}

/** dinheiro(150.5) -> "R$150,50" */
function dinheiro(float $valor, int $casas = 2): string
{
    return 'R$' . number_format($valor, $casas, ',', '.');
}
