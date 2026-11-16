<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Proteção contra CSRF: todo formulário POST carrega um token secreto da sessão.
 * Um site malicioso não conhece o token, então não consegue disparar ações por você.
 */
final class Csrf
{
    public static function token(): string
    {
        $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    /** HTML do campo escondido: use dentro de todo <form method="post">. */
    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function validar(?string $enviado): bool
    {
        $esperado = $_SESSION['_csrf'] ?? null;
        return is_string($esperado) && is_string($enviado) && hash_equals($esperado, $enviado);
    }
}
