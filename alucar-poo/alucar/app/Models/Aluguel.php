<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusAluguel;
use App\Exceptions\RegraDeNegocioException;
use DateTimeImmutable;

/**
 * Um aluguel: liga um cliente a um carro por um período.
 * As regras de "datas válidas" e "cálculo do valor" moram aqui, não no controller.
 * Anteriormente usava conex
 */
final class Aluguel
{
    private function __construct(
        private ?int $id,
        private int $clienteId,
        private int $carroId,
        private ?int $vendedorId,
        private DateTimeImmutable $dataInicio,
        private DateTimeImmutable $dataFim,
        private float $valorTotal,
        private StatusAluguel $status
    ) {
    }

    /**
     * Registra um aluguel novo para o carro informado.
     * $hoje é parâmetro só para os testes conseguirem fixar a data.
     */
    public static function registrar(
        Carro $carro,
        int $clienteId,
        ?int $vendedorId,
        DateTimeImmutable $inicio,
        DateTimeImmutable $fim,
        ?DateTimeImmutable $hoje = null
    ): self {
        $hoje ??= new DateTimeImmutable('today');

        if ($inicio < $hoje) {
            throw new RegraDeNegocioException('A data de início não pode ser no passado.');
        }

        $dias = (int) $inicio->diff($fim)->format('%r%a');
        if ($dias < 1) {
            throw new RegraDeNegocioException('A data final precisa ser depois da data inicial.');
        }

        return new self(
            null,
            $clienteId,
            (int) $carro->getId(),
            $vendedorId,
            $inicio,
            $fim,
            $carro->calcularValor($dias),
            StatusAluguel::Confirmado
        );
    }

    public static function hidratar(array $linha): self
    {
        return new self(
            (int) $linha['id'],
            (int) $linha['cliente_id'],
            (int) $linha['carro_id'],
            $linha['vendedor_id'] !== null ? (int) $linha['vendedor_id'] : null,
            new DateTimeImmutable($linha['data_inicio']),
            new DateTimeImmutable($linha['data_fim']),
            (float) $linha['valor_total'],
            StatusAluguel::from($linha['status'])
        );
    }

    public function getId(): ?int { return $this->id; }
    public function getClienteId(): int { return $this->clienteId; }
    public function getCarroId(): int { return $this->carroId; }
    public function getVendedorId(): ?int { return $this->vendedorId; }
    public function getDataInicio(): DateTimeImmutable { return $this->dataInicio; }
    public function getDataFim(): DateTimeImmutable { return $this->dataFim; }
    public function getValorTotal(): float { return $this->valorTotal; }
    public function getStatus(): StatusAluguel { return $this->status; }

    public function estaAtivo(): bool
    {
        return $this->status->estaAtivo();
    }

    /** Admin devolve qualquer aluguel; vendedor só os que ele mesmo realizou. */
    public function podeSerDevolvidoPor(Usuario $usuario): bool
    {
        return $usuario->isAdmin()
            || ($usuario->isVendedor() && $this->vendedorId === $usuario->getId());
    }

    public function finalizar(): void
    {
        if (!$this->estaAtivo()) {
            throw new RegraDeNegocioException('Esse aluguel já foi encerrado.');
        }
        $this->status = StatusAluguel::Finalizado;
    }
}
