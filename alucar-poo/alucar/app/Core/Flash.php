<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mensagens de uma única exibição ("Carro cadastrado!"). Ficam na sessão até a
 * próxima página mostrá-las. Substitui os ?error=... na URL e os if/elseif
 * repetidos em cada view.
 */
final class Flash
{
    public static function sucesso(string $mensagem): void
    {
        $_SESSION['_flash'][] = ['sucesso', $mensagem];
    }

    public static function erro(string $mensagem): void
    {
        $_SESSION['_flash'][] = ['erro', $mensagem];
    }

    /** Imprime as mensagens pendentes e as apaga. */
    public static function exibir(): void
    {
        $mensagens = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        foreach ($mensagens as [$tipo, $texto]) {
            $classe = $tipo === 'sucesso' ? 'alert-success' : 'alert-error';
            echo '<p class="' . $classe . '">' . e($texto) . '</p>' . PHP_EOL;
        }
    }
}
