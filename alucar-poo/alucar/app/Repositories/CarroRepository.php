<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\StatusCarro;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Carro;
use PDO;
use PDOException;

/** Único lugar que escreve SQL da tabela "carros". */
class CarroRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return Carro[] */
    public function listar(): array
    {
        $linhas = $this->pdo->query('SELECT * FROM carros ORDER BY marca, modelo')->fetchAll();
        return array_map([Carro::class, 'hidratar'], $linhas);
    }

    /** @return Carro[] */
    public function listarDisponiveis(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM carros WHERE status = ? ORDER BY marca, modelo');
        $stmt->execute([StatusCarro::Disponivel->value]);
        return array_map([Carro::class, 'hidratar'], $stmt->fetchAll());
    }

    public function buscarPorId(int $id): ?Carro
    {
        $stmt = $this->pdo->prepare('SELECT * FROM carros WHERE id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();
        return $linha ? Carro::hidratar($linha) : null;
    }

    public function contarPorStatus(StatusCarro $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM carros WHERE status = ?');
        $stmt->execute([$status->value]);
        return (int) $stmt->fetchColumn();
    }

    public function inserir(Carro $carro): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO carros (marca, modelo, ano, placa, valor_diaria, status) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $carro->getMarca(),
                $carro->getModelo(),
                $carro->getAno(),
                $carro->getPlaca(),
                $carro->getValorDiaria(),
                $carro->getStatus()->value,
            ]);
        } catch (PDOException $e) {
            // 23000 = violação de constraint (aqui: placa duplicada)
            if ($e->getCode() === '23000') {
                throw new RegraDeNegocioException('Já existe um carro cadastrado com essa placa.');
            }
            throw $e;
        }
    }

    /**
     * Grava o novo status SÓ SE o carro ainda estiver no status anterior.
     * É a trava contra "dois clientes alugando o mesmo carro ao mesmo tempo":
     * o segundo UPDATE não encontra mais o status antigo e devolve false.
     */
    public function atualizarStatus(Carro $carro, StatusCarro $statusAnterior): bool
    {
        $stmt = $this->pdo->prepare('UPDATE carros SET status = ? WHERE id = ? AND status = ?');
        $stmt->execute([$carro->getStatus()->value, $carro->getId(), $statusAnterior->value]);
        return $stmt->rowCount() === 1;
    }

    public function excluir(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM carros WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // 23000 = violação de FK: o carro tem aluguéis no histórico.
            if ($e->getCode() === '23000') {
                throw new RegraDeNegocioException('Não é possível excluir: esse carro possui aluguéis no histórico.');
            }
            throw $e;
        }
    }
}
