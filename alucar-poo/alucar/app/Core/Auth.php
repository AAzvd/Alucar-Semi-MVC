<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

/**
 * Quem está logado. Guardamos só o ID na sessão e buscamos o usuário no banco
 * (uma vez por requisição). Assim, se um admin for rebaixado, a mudança vale
 * na hora, e não só no próximo login.
 *
 * Os métodos são static porque a sessão é única: não faz sentido ter "vários Auth".
 */
final class Auth
{
    private static ?Usuario $usuario = null;
    private static bool $carregado = false;

    public static function entrar(Usuario $usuario): void
    {
        session_regenerate_id(true); // evita "session fixation"
        $_SESSION['usuario_id'] = $usuario->getId();
        self::$usuario = $usuario;
        self::$carregado = true;
    }

    public static function sair(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::reiniciar();
    }

    public static function usuario(): ?Usuario
    {
        if (!self::$carregado) {
            $id = $_SESSION['usuario_id'] ?? null;
            self::$usuario = $id ? Container::usuarioRepository()->buscarPorId((int) $id) : null;
            self::$carregado = true;
        }
        return self::$usuario;
    }

    public static function logado(): bool
    {
        return self::usuario() !== null;
    }

    /** Para páginas e ações protegidas: sem login, vai para a tela de login. */
    public static function exigirLogin(): Usuario
    {
        $usuario = self::usuario();
        if ($usuario === null) {
            redirecionar('views/login.php');
        }
        return $usuario;
    }

    /** Primeira tela de cada perfil: admin/vendedor -> painel; cliente -> catálogo. */
    public static function paginaInicial(): string
    {
        $usuario = self::usuario();
        if ($usuario === null) {
            return 'views/login.php';
        }
        return $usuario->podeGerenciarUsuarios() ? 'views/dashboard.php' : 'views/carros/carros.php';
    }

    public static function reiniciar(): void
    {
        self::$usuario = null;
        self::$carregado = false;
    }
}
