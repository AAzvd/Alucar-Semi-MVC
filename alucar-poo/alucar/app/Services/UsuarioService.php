<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipoUsuario;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use DateTimeImmutable;

/**
 * Regras de usuários. Todo método que muda algo recebe QUEM está pedindo ($ator),
 * então a checagem de permissão fica aqui e não depende da tela estar escondendo o botão.
 */
class UsuarioService
{
    public function __construct(private UsuarioRepository $usuarios)
    {
    }

    /**
     * Cadastro. $ator é null no cadastro público (visitante), que sempre vira cliente.
     * Só um admin logado pode escolher outro tipo.
     */
    public function cadastrar(?Usuario $ator, string $nome, string $email, string $senha, ?string $tipo): Usuario
    {
        $tipoFinal = TipoUsuario::Cliente;
        if ($ator !== null && $ator->isAdmin()) {
            $tipoFinal = TipoUsuario::tryFrom((string) $tipo) ?? TipoUsuario::Cliente;
        }

        $usuario = Usuario::cadastrar($nome, $email, $senha, $tipoFinal);
        $this->usuarios->inserir($usuario);
        return $usuario;
    }

    /** Usuário para a tela de edição, já checando se $ator pode editá-lo. */
    public function buscarParaEdicao(Usuario $ator, int $id): Usuario
    {
        $alvo = $this->usuarios->buscarPorId($id)
            ?? throw new RegraDeNegocioException('Usuário não encontrado.');

        if (!$ator->podeEditar($alvo)) {
            throw new AcessoNegadoException();
        }
        return $alvo;
    }

    public function editar(Usuario $ator, int $id, string $nome, string $email, ?string $novaSenha, ?string $tipo): void
    {
        $alvo = $this->buscarParaEdicao($ator, $id);

        $alvo->alterarDados($nome, $email);

        if ($novaSenha !== null && $novaSenha !== '') {
            $alvo->alterarSenha($novaSenha);
        }

        // Só admin muda o tipo de alguém, e ninguém muda o próprio (evita ficar sem admin).
        $novoTipo = TipoUsuario::tryFrom((string) $tipo);
        if ($novoTipo !== null && $ator->isAdmin() && !$ator->mesmoQue($alvo)) {
            $alvo->alterarTipo($novoTipo);
        }

        $this->usuarios->atualizar($alvo);
    }

    public function excluir(Usuario $ator, int $id): void
    {
        $alvo = $this->usuarios->buscarPorId($id)
            ?? throw new RegraDeNegocioException('Usuário não encontrado.');

        if (!$ator->podeExcluir($alvo)) {
            throw new AcessoNegadoException();
        }
        $this->usuarios->excluir($id);
    }

    /** @return array<int, array{usuario: Usuario, ativos: int, atrasados: int}> */
    public function listarParaPainel(Usuario $ator): array
    {
        $this->exigirGestor($ator);
        return $this->usuarios->listarComResumo(new DateTimeImmutable('today'));
    }

    /** @return Usuario[] */
    public function listarClientes(Usuario $ator): array
    {
        $this->exigirGestor($ator);
        return $this->usuarios->listarClientes();
    }

    private function exigirGestor(Usuario $ator): void
    {
        if (!$ator->podeGerenciarUsuarios()) {
            throw new AcessoNegadoException();
        }
    }
}
