<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoUsuario;
use App\Exceptions\RegraDeNegocioException;

/**
 * Um usuário do sistema (cliente, vendedor ou admin) e as regras de permissão dele.
 *
 * Duas formas de criar:
 *  - Usuario::cadastrar(...)  -> usuário NOVO: valida os dados e gera o hash da senha.
 *  - Usuario::hidratar(...)   -> reconstrói a partir de uma linha do banco (já é confiável).
 * O construtor é private justamente para obrigar a usar uma dessas duas.
 */
final class Usuario
{
    private const SENHA_MINIMA = 8;

    private function __construct(
        private ?int $id,
        private string $nome,
        private string $email,
        private string $senhaHash,
        private TipoUsuario $tipo
    ) {
    }

    public static function cadastrar(
        string $nome,
        string $email,
        string $senha,
        TipoUsuario $tipo = TipoUsuario::Cliente
    ): self {
        return new self(
            null,
            self::validarNome($nome),
            self::validarEmail($email),
            self::gerarHash($senha),
            $tipo
        );
    }

    public static function hidratar(array $linha): self
    {
        return new self(
            (int) $linha['id'],
            $linha['nome'],
            $linha['email'],
            $linha['senha'],
            TipoUsuario::from($linha['tipo'])
        );
    }

    // ---------- Leitura ----------

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): string { return $this->email; }
    public function getSenhaHash(): string { return $this->senhaHash; }
    public function getTipo(): TipoUsuario { return $this->tipo; }

    public function isAdmin(): bool { return $this->tipo === TipoUsuario::Admin; }
    public function isVendedor(): bool { return $this->tipo === TipoUsuario::Vendedor; }
    public function isCliente(): bool { return $this->tipo === TipoUsuario::Cliente; }

    public function mesmoQue(Usuario $outro): bool
    {
        return $this->id !== null && $this->id === $outro->id;
    }

    public function verificarSenha(string $senha): bool
    {
        return password_verify($senha, $this->senhaHash);
    }

    // ---------- Permissões (as regras que antes eram funcoes.php) ----------

    /** Acessa o painel e a lista de usuários. */
    public function podeGerenciarUsuarios(): bool
    {
        return $this->isAdmin() || $this->isVendedor();
    }

    /** Cadastra e exclui carros do catálogo. */
    public function podeGerenciarCarros(): bool
    {
        return $this->isAdmin() || $this->isVendedor();
    }

    /**
     * Admin edita qualquer um; cada um edita a si mesmo; vendedor edita só clientes.
     * (Antes, um vendedor conseguia trocar a senha de um admin.)
     */
    public function podeEditar(Usuario $alvo): bool
    {
        return $this->isAdmin()
            || $this->mesmoQue($alvo)
            || ($this->isVendedor() && $alvo->isCliente());
    }

    /** Só admin exclui, e nunca a si mesmo. */
    public function podeExcluir(Usuario $alvo): bool
    {
        return $this->isAdmin() && !$this->mesmoQue($alvo);
    }

    // ---------- Alterações (sempre revalidam) ----------

    public function alterarDados(string $nome, string $email): void
    {
        $this->nome = self::validarNome($nome);
        $this->email = self::validarEmail($email);
    }

    public function alterarSenha(string $senha): void
    {
        $this->senhaHash = self::gerarHash($senha);
    }

    public function alterarTipo(TipoUsuario $tipo): void
    {
        $this->tipo = $tipo;
    }

    // ---------- Validações ----------

    private static function validarNome(string $nome): string
    {
        $nome = trim($nome);
        if ($nome === '' || mb_strlen($nome) > 255) {
            throw new RegraDeNegocioException('Informe um nome válido (até 255 caracteres).');
        }
        return $nome;
    }

    private static function validarEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            throw new RegraDeNegocioException('Informe um e-mail válido.');
        }
        return $email;
    }

    private static function gerarHash(string $senha): string
    {
        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            throw new RegraDeNegocioException('A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.');
        }
        return password_hash($senha, PASSWORD_DEFAULT);
    }
}
