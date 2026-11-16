<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Container;
use App\Core\Flash;
use App\Services\CarroService;
use Throwable;

class CarroController extends Controller
{
    private const TELA = 'views/carros/carros.php';

    private CarroService $carros;

    public function __construct(?CarroService $carros = null)
    {
        $this->carros = $carros ?? Container::carroService();
    }

    /** Dados da tela de carros. */
    public function index(): array
    {
        $ator = Auth::exigirLogin();
        return ['ator' => $ator, 'carros' => $this->carros->listar()];
    }

    public function criar(): void
    {
        $this->exigirPost();
        $ator = Auth::exigirLogin();

        try {
            $this->carros->cadastrar(
                $ator,
                $this->campo('marca'),
                $this->campo('modelo'),
                (int) $this->campo('ano'),
                $this->campo('placa'),
                (float) str_replace(',', '.', $this->campo('valor_diaria'))
            );
        } catch (Throwable $e) {
            $this->falhar($e, self::TELA);
        }

        Flash::sucesso('Carro cadastrado com sucesso!');
        redirecionar(self::TELA);
    }

    public function excluir(): void
    {
        $this->exigirPost();
        $ator = Auth::exigirLogin();

        try {
            $this->carros->excluir($ator, (int) $this->campo('id'));
        } catch (Throwable $e) {
            $this->falhar($e, self::TELA);
        }

        Flash::sucesso('Carro excluído.');
        redirecionar(self::TELA);
    }
}
