<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

class AuthService
{
    public function __construct(private UsuarioRepository $usuarios)
    {
    }

    /** Confere e-mail e senha. A mensagem é a mesma nos dois erros, para não revelar quais e-mails existem. */
    public function autenticar(string $email, string $senha): Usuario
    {
        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null) {
            // Gasta o mesmo tempo de uma verificação real (evita descobrir e-mails pelo tempo de resposta).
            password_verify($senha, '$2y$10$ft1prvZf55L4NNgILYFoO.9cl0tpjaVue8COWSntxGK0ETXotK3K6');
            throw new RegraDeNegocioException('E-mail ou senha inválidos.');
        }
        if (!$usuario->verificarSenha($senha)) {
            throw new RegraDeNegocioException('E-mail ou senha inválidos.');
        }
        return $usuario;
    }
}
