<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Container;
use App\Services\PainelService;
use App\Services\UsuarioService;

class PainelController extends Controller
{
    private PainelService $painel;
    private UsuarioService $usuarios;

    public function __construct(?PainelService $painel = null, ?UsuarioService $usuarios = null)
    {
        $this->painel = $painel ?? Container::painelService();
        $this->usuarios = $usuarios ?? Container::usuarioService();
    }

    /** Dados do dashboard. Cliente não tem painel: vai para o catálogo. */
    public function index(): array
    {
        $ator = Auth::exigirLogin();
        if (!$ator->podeGerenciarUsuarios()) {
            redirecionar('views/carros/carros.php');
        }

        return [
            'ator' => $ator,
            'resumo' => $this->painel->resumo(),
            'linhas' => $this->usuarios->listarParaPainel($ator),
        ];
    }
}
