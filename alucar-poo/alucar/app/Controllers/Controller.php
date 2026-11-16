<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use Throwable;

/**
 * Base dos controllers. O trabalho de um controller é só:
 *   1) ler a requisição  2) chamar um Service  3) escolher para onde redirecionar.
 * Regra de negócio NÃO fica aqui.
 */
abstract class Controller
{
    /** Ações que alteram dados só aceitam POST com token CSRF válido. */
    protected function exigirPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Método não permitido.');
        }

        if (!Csrf::validar($this->campo('_csrf'))) {
            Flash::erro('Sua sessão expirou. Tente novamente.');
            redirecionar(Auth::paginaInicial());
        }
    }

    /** Lê um campo do formulário como texto (ignora valores que não sejam texto). */
    protected function campo(string $nome): string
    {
        $valor = $_POST[$nome] ?? '';
        return is_string($valor) ? $valor : '';
    }

    /** Mostra a mensagem certa e volta para $destino. Erros inesperados vão pro log. */
    protected function falhar(Throwable $e, string $destino): never
    {
        if ($e instanceof \DomainException) {
            Flash::erro($e->getMessage());
        } else {
            error_log((string) $e);
            Flash::erro('Ocorreu um erro inesperado. Tente novamente.');
        }
        redirecionar($destino);
    }
}
