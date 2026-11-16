<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Exceptions\RegraDeNegocioException;
use App\Models\Carro;
use App\Models\Usuario;
use App\Repositories\CarroRepository;

class CarroService
{
    public function __construct(private CarroRepository $carros)
    {
    }

    /** @return Carro[] */
    public function listar(): array
    {
        return $this->carros->listar();
    }

    /** @return Carro[] */
    public function listarDisponiveis(): array
    {
        return $this->carros->listarDisponiveis();
    }

    public function cadastrar(Usuario $ator, string $marca, string $modelo, int $ano, string $placa, float $valorDiaria): void
    {
        $this->exigirGestor($ator);
        $this->carros->inserir(Carro::cadastrar($marca, $modelo, $ano, $placa, $valorDiaria));
    }

    public function excluir(Usuario $ator, int $id): void
    {
        $this->exigirGestor($ator);

        $carro = $this->carros->buscarPorId($id)
            ?? throw new RegraDeNegocioException('Carro não encontrado.');

        if (!$carro->podeSerExcluido()) {
            throw new RegraDeNegocioException('Esse carro está alugado no momento: só pode ser excluído depois de devolvido.');
        }
        $this->carros->excluir($id);
    }

    private function exigirGestor(Usuario $ator): void
    {
        if (!$ator->podeGerenciarCarros()) {
            throw new AcessoNegadoException();
        }
    }
}
