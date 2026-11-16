<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\StatusAluguel;
use App\Models\Aluguel;
use PDO;

/** Único lugar que escreve SQL da tabela "alugueis". */
class AluguelRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function inserir(Aluguel $aluguel): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO alugueis (cliente_id, carro_id, vendedor_id, data_inicio, data_fim, valor_total, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $aluguel->getClienteId(),
            $aluguel->getCarroId(),
            $aluguel->getVendedorId(),
            $aluguel->getDataInicio()->format('Y-m-d'),
            $aluguel->getDataFim()->format('Y-m-d'),
            $aluguel->getValorTotal(),
            $aluguel->getStatus()->value,
        ]);
    }

    public function buscarPorId(int $id): ?Aluguel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM alugueis WHERE id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();
        return $linha ? Aluguel::hidratar($linha) : null;
    }

    /** Mesma ideia do carro: só muda se o aluguel ainda estiver no status anterior. */
    public function atualizarStatus(Aluguel $aluguel, StatusAluguel $statusAnterior): bool
    {
        $stmt = $this->pdo->prepare('UPDATE alugueis SET status = ? WHERE id = ? AND status = ?');
        $stmt->execute([$aluguel->getStatus()->value, $aluguel->getId(), $statusAnterior->value]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Listagem para a tela: traz também nome do cliente, dados do carro e nome do vendedor (JOIN).
     * Devolve arrays (e não objetos Aluguel) porque são dados só para exibição,
     * misturando várias tabelas; um "read model".
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarDetalhado(?int $clienteId, ?int $vendedorId, ?StatusAluguel $status): array
    {
        $sql = "SELECT a.*,
                       c.nome AS cliente_nome,
                       ca.marca, ca.modelo, ca.placa,
                       v.nome AS vendedor_nome
                FROM alugueis a
                JOIN usuarios c ON a.cliente_id = c.id
                JOIN carros ca ON a.carro_id = ca.id
                LEFT JOIN usuarios v ON a.vendedor_id = v.id
                WHERE 1=1";
        $params = [];

        if ($clienteId !== null) {
            $sql .= ' AND a.cliente_id = ?';
            $params[] = $clienteId;
        }
        if ($vendedorId !== null) {
            $sql .= ' AND a.vendedor_id = ?';
            $params[] = $vendedorId;
        }
        if ($status !== null) {
            $sql .= ' AND a.status = ?';
            $params[] = $status->value;
        }

        $stmt = $this->pdo->prepare($sql . ' ORDER BY a.id DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function contarAtivos(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM alugueis WHERE status IN ('confirmado','em_andamento')")
            ->fetchColumn();
    }

    public function faturamento(): float
    {
        return (float) $this->pdo
            ->query("SELECT COALESCE(SUM(valor_total), 0) FROM alugueis WHERE status = 'finalizado'")
            ->fetchColumn();
    }
}
