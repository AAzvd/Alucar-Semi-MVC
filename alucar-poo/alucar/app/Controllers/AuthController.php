<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Container;
use App\Services\AuthService;
use Throwable;

class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? Container::authService();
    }

    public function login(): void
    {
        $this->exigirPost();

        try {
            $usuario = $this->auth->autenticar($this->campo('email'), $this->campo('senha'));
            Auth::entrar($usuario);
            redirecionar(Auth::paginaInicial());
        } catch (Throwable $e) {
            $this->falhar($e, 'views/login.php');
        }
    }

    public function logout(): void
    {
        $this->exigirPost();
        Auth::sair();
        redirecionar('views/login.php');
    }
}
