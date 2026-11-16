<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Enums\StatusAluguel;
use App\Exceptions\AcessoNegadoException;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Aluguel;
use App\Models\Usuario;
use App\Repositories\AluguelRepository;
use App\Repositories\CarroRepository;
use App\Repositories\UsuarioRepository;
use DateTimeImmutable;
use PDO;

/**
 * O caso de uso mais importante do sistema: alugar e devolver.
 * Envolve DOIS registros (aluguel + status do carro), por isso usa transação.
 */
class AluguelService
{
    private const MSG_INDISPONIVEL = 'Esse carro não está mais disponível. Escolha outro.';

    public function __construct(
        private PDO $pdo,
        private CarroRepository $carros,
        private AluguelRepository $alugueis,
        private UsuarioRepository $usuarios
    ) {
    }

    /**
     * Cliente sempre aluga para si mesmo; admin/vendedor alugam em nome de um cliente
     * e ficam registrados como responsáveis (vendedor_id).
     */
    public function alugar(Usuario $ator, int $clienteId, int $carroId, string $inicio, string $fim): void
    {
        if ($ator->isCliente()) {
            $clienteId = (int) $ator->getId();
            $vendedorId = null;
        } else {
            $cliente = $this->usuarios->buscarPorId($clienteId);
            if ($cliente === null || !$cliente->isCliente()) {
                throw new RegraDeNegocioException('Selecione um cliente válido.');
            }
            $vendedorId = $ator->getId();
        }

        $carro = $this->carros->buscarPorId($carroId)
            ?? throw new RegraDeNegocioException(self::MSG_INDISPONIVEL);

        $aluguel = Aluguel::registrar($carro, $clienteId, $vendedorId, $this->data($inicio), $this->data($fim));

        $statusAnterior = $carro->getStatus();
        $carro->marcarComoAlugado(); // lança exceção se o carro não estiver disponível

        // Transação: ou grava os dois (carro + aluguel) ou nenhum.
        Database::transacao($this->pdo, function () use ($carro, $statusAnterior, $aluguel): void {
            // Esta linha também protege contra dois pedidos simultâneos pelo mesmo carro.
            if (!$this->carros->atualizarStatus($carro, $statusAnterior)) {
                throw new RegraDeNegocioException(self::MSG_INDISPONIVEL);
            }
            $this->alugueis->inserir($aluguel);
        });
    }

    public function devolver(Usuario $ator, int $aluguelId): void
    {
        if (!$ator->podeGerenciarUsuarios()) { // cliente não devolve carro
            throw new AcessoNegadoException();
        }

        $aluguel = $this->alugueis->buscarPorId($aluguelId)
            ?? throw new RegraDeNegocioException('Aluguel não encontrado.');

        if (!$aluguel->podeSerDevolvidoPor($ator)) {
            throw new AcessoNegadoException();
        }

        $carro = $this->carros->buscarPorId($aluguel->getCarroId())
            ?? throw new RegraDeNegocioException('Carro do aluguel não encontrado.');

        $statusAluguelAnterior = $aluguel->getStatus();
        $statusCarroAnterior = $carro->getStatus();

        $aluguel->finalizar(); // lança exceção se já estiver encerrado
        $carro->liberar();

        Database::transacao($this->pdo, function () use ($aluguel, $statusAluguelAnterior, $carro, $statusCarroAnterior): void {
            if (!$this->alugueis->atualizarStatus($aluguel, $statusAluguelAnterior)) {
                throw new RegraDeNegocioException('Esse aluguel já foi encerrado.');
            }
            if ($carro->getStatus() !== $statusCarroAnterior) {
                $this->carros->atualizarStatus($carro, $statusCarroAnterior);
            }
        });
    }

    /**
     * Cada perfil enxerga um recorte:
     * cliente -> só os próprios; vendedor -> só os que ele realizou; admin -> todos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarPara(Usuario $ator, ?StatusAluguel $status = null): array
    {
        return $this->alugueis->listarDetalhado(
            $ator->isCliente() ? $ator->getId() : null,
            $ator->isVendedor() ? $ator->getId() : null,
            $status
        );
    }

    private function data(string $valor): DateTimeImmutable
    {
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if ($data === false || $data->format('Y-m-d') !== $valor) {
            throw new RegraDeNegocioException('Informe datas válidas.');
        }
        return $data;
    }
}
