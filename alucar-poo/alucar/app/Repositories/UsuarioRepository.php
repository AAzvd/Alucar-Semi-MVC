<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TipoUsuario;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Usuario;
use DateTimeImmutable;
use PDO;
use PDOException;

/** Único lugar que escreve SQL da tabela "usuarios". Recebe e devolve objetos Usuario. */
class UsuarioRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();
        return $linha ? Usuario::hidratar($linha) : null;
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([mb_strtolower(trim($email))]);
        $linha = $stmt->fetch();
        return $linha ? Usuario::hidratar($linha) : null;
    }

    /** @return Usuario[] */
    public function listarClientes(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE tipo = ? ORDER BY nome');
        $stmt->execute([TipoUsuario::Cliente->value]);
        return array_map([Usuario::class, 'hidratar'], $stmt->fetchAll());
    }

    /**
     * Lista para o painel: cada usuário + quantos aluguéis ativos e atrasados ele tem.
     * A data de hoje vem por parâmetro (em vez de CURDATE()) para funcionar em qualquer banco.
     *
     * @return array<int, array{usuario: Usuario, ativos: int, atrasados: int}>
     */
    public function listarComResumo(DateTimeImmutable $hoje): array
    {
        $sql = "SELECT u.*,
                    (SELECT COUNT(*) FROM alugueis a
                      WHERE a.cliente_id = u.id AND a.status IN ('confirmado','em_andamento')) AS ativos,
                    (SELECT COUNT(*) FROM alugueis a
                      WHERE a.cliente_id = u.id AND a.status IN ('confirmado','em_andamento')
                        AND a.data_fim < ?) AS atrasados
                FROM usuarios u
                ORDER BY u.tipo, u.nome";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hoje->format('Y-m-d')]);

        $resultado = [];
        foreach ($stmt->fetchAll() as $linha) {
            $resultado[] = [
                'usuario' => Usuario::hidratar($linha),
                'ativos' => (int) $linha['ativos'],
                'atrasados' => (int) $linha['atrasados'],
            ];
        }
        return $resultado;
    }

    public function contarPorTipo(TipoUsuario $tipo): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE tipo = ?');
        $stmt->execute([$tipo->value]);
        return (int) $stmt->fetchColumn();
    }

    /** @return int o id gerado */
    public function inserir(Usuario $usuario): int
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $usuario->getNome(),
                $usuario->getEmail(),
                $usuario->getSenhaHash(),
                $usuario->getTipo()->value,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw $this->traduzir($e);
        }
    }

    public function atualizar(Usuario $usuario): void
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, senha = ?, tipo = ? WHERE id = ?');
            $stmt->execute([
                $usuario->getNome(),
                $usuario->getEmail(),
                $usuario->getSenhaHash(),
                $usuario->getTipo()->value,
                $usuario->getId(),
            ]);
        } catch (PDOException $e) {
            throw $this->traduzir($e);
        }
    }

    public function excluir(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // 23000 = violação de FK: o usuário tem aluguéis no histórico.
            if ($e->getCode() === '23000') {
                throw new RegraDeNegocioException('Não é possível excluir: esse usuário tem aluguéis no histórico.');
            }
            throw $e;
        }
    }

    /** 23000 = violação de constraint; aqui a única UNIQUE usada é o e-mail. */
    private function traduzir(PDOException $e): \Throwable
    {
        if ($e->getCode() === '23000') {
            return new RegraDeNegocioException('Esse e-mail já está cadastrado.');
        }
        return $e;
    }
}
