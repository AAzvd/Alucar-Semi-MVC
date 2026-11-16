<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Container;
use App\Core\Flash;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Throwable;

class UsuarioController extends Controller
{
    private UsuarioService $usuarios;

    public function __construct(?UsuarioService $usuarios = null)
    {
        $this->usuarios = $usuarios ?? Container::usuarioService();
    }

    /** Dados da tela de edição (GET ?id=...). */
    public function formularioEdicao(): array
    {
        $ator = Auth::exigirLogin();
        $id = (int) ($_GET['id'] ?? 0);

        try {
            return ['ator' => $ator, 'alvo' => $this->usuarios->buscarParaEdicao($ator, $id)];
        } catch (Throwable $e) {
            $this->falhar($e, Auth::paginaInicial());
        }
    }

    /**
     * Serve para os dois cadastros: o público (visitante vira cliente) e o do
     * painel (admin escolhe o tipo). Quem decide é o service, olhando o $ator.
     */
    public function criar(): void
    {
        $this->exigirPost();
        $ator = Auth::usuario();

        try {
            $this->usuarios->cadastrar(
                $ator,
                $this->campo('nome'),
                $this->campo('email'),
                $this->campo('senha'),
                $this->campo('tipo')
            );
        } catch (Throwable $e) {
            $this->falhar($e, $ator ? Auth::paginaInicial() : 'views/criaUsuarioForm.php');
        }

        if ($ator) {
            Flash::sucesso('Usuário cadastrado com sucesso!');
            redirecionar(Auth::paginaInicial());
        }
        Flash::sucesso('Cadastro realizado! Faça login para continuar.');
        redirecionar('views/login.php');
    }

    public function editar(): void
    {
        $this->exigirPost();
        $ator = Auth::exigirLogin();
        $id = (int) $this->campo('id');

        try {
            $this->usuarios->editar(
                $ator,
                $id,
                $this->campo('nome'),
                $this->campo('email'),
                $this->campo('senha'),
                $this->campo('tipo')
            );
        } catch (Throwable $e) {
            $this->falhar($e, 'views/editaUsuarioForm.php?id=' . $id);
        }

        Flash::sucesso('Usuário atualizado.');
        redirecionar(Auth::paginaInicial());
    }

    public function excluir(): void
    {
        $this->exigirPost();
        $ator = Auth::exigirLogin();

        try {
            $this->usuarios->excluir($ator, (int) $this->campo('id'));
        } catch (Throwable $e) {
            $this->falhar($e, Auth::paginaInicial());
        }

        Flash::sucesso('Usuário excluído.');
        redirecionar(Auth::paginaInicial());
    }
}
