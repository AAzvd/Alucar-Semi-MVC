<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Container;
use App\Core\Flash;
use App\Enums\StatusAluguel;
use App\Services\AluguelService;
use App\Services\CarroService;
use App\Services\UsuarioService;
use Throwable;

class AluguelController extends Controller
{
    private const TELA = 'views/alugueis/alugueis.php';

    private AluguelService $alugueis;
    private CarroService $carros;
    private UsuarioService $usuarios;

    public function __construct(
        ?AluguelService $alugueis = null,
        ?CarroService $carros = null,
        ?UsuarioService $usuarios = null
    ) {
        $this->alugueis = $alugueis ?? Container::aluguelService();
        $this->carros = $carros ?? Container::carroService();
        $this->usuarios = $usuarios ?? Container::usuarioService();
    }

    /** Dados da tela de aluguéis (com filtro opcional ?status=finalizado). */
    public function index(): array
    {
        $ator = Auth::exigirLogin();

        $filtro = is_string($_GET['status'] ?? null) ? StatusAluguel::tryFrom($_GET['status']) : null;

        return [
            'ator' => $ator,
            'filtro' => $filtro,
            'alugueis' => $this->alugueis->listarPara($ator, $filtro),
            'clientes' => $ator->isCliente() ? [] : $this->usuarios->listarClientes($ator),
            'carrosDisponiveis' => $this->carros->listarDisponiveis(),
        ];
    }

    public function criar(): void
    {
        $this->exigirPost();
        $ator = Auth::exigirLogin();

        try {
            $this->alugueis->alugar(
                $ator,
                (int) $this->campo('cliente_id'),
                (int) $this->campo('carro_id'),
                $this->campo('data_inicio'),
                $this->campo('data_fim')
            );
        } catch (Throwable $e) {
            $this->falhar($e, self::TELA);
        }

        Flash::sucesso('Aluguel registrado com sucesso!');
        redirecionar(self::TELA);
    }

    public function devolver(): void
    {
        $this->exigirPost();
        $ator = Auth::exigirLogin();

        try {
            $this->alugueis->devolver($ator, (int) $this->campo('id'));
        } catch (Throwable $e) {
            $this->falhar($e, self::TELA);
        }

        Flash::sucesso('Devolução confirmada.');
        redirecionar(self::TELA);
    }
}
