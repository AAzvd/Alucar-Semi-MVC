<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusCarro;
use App\Enums\TipoUsuario;
use App\Repositories\AluguelRepository;
use App\Repositories\CarroRepository;
use App\Repositories\UsuarioRepository;

/** Números dos cartões do painel. */
class PainelService
{
    public function __construct(
        private CarroRepository $carros,
        private AluguelRepository $alugueis,
        private UsuarioRepository $usuarios
    ) {
    }

    /** @return array{carrosDisponiveis: int, alugueisAtivos: int, totalClientes: int, faturamento: float} */
    public function resumo(): array
    {
        return [
            'carrosDisponiveis' => $this->carros->contarPorStatus(StatusCarro::Disponivel),
            'alugueisAtivos' => $this->alugueis->contarAtivos(),
            'totalClientes' => $this->usuarios->contarPorTipo(TipoUsuario::Cliente),
            'faturamento' => $this->alugueis->faturamento(),
        ];
    }
}
