<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusCarro;
use App\Exceptions\RegraDeNegocioException;

/**
 * Um carro da frota. Repare que NÃO existe setStatus(): o status só muda pelos
 * métodos marcarComoAlugado() e liberar(), que respeitam as regras (encapsulamento).
 */
final class Carro
{
    private const ANO_MINIMO = 1950;

    private function __construct(
        private ?int $id,
        private string $marca,
        private string $modelo,
        private int $ano,
        private string $placa,
        private float $valorDiaria,
        private StatusCarro $status
    ) {
    }

    /** Carro novo: valida e normaliza os dados (placa em maiúsculas, sem hífen). */
    public static function cadastrar(
        string $marca,
        string $modelo,
        int $ano,
        string $placa,
        float $valorDiaria
    ): self {
        $marca = trim($marca);
        $modelo = trim($modelo);
        if ($marca === '' || $modelo === '') {
            throw new RegraDeNegocioException('Informe a marca e o modelo do carro.');
        }

        $anoMaximo = (int) date('Y') + 1;
        if ($ano < self::ANO_MINIMO || $ano > $anoMaximo) {
            throw new RegraDeNegocioException("O ano deve estar entre " . self::ANO_MINIMO . " e $anoMaximo.");
        }

        // Aceita placa antiga (ABC1234) e Mercosul (ABC1D23).
        $placa = strtoupper(str_replace(['-', ' '], '', trim($placa)));
        if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa)) {
            throw new RegraDeNegocioException('Placa inválida. Use o formato ABC1234 ou ABC1D23.');
        }

        if ($valorDiaria <= 0) {
            throw new RegraDeNegocioException('O valor da diária deve ser maior que zero.');
        }

        return new self(null, $marca, $modelo, $ano, $placa, round($valorDiaria, 2), StatusCarro::Disponivel);
    }

    /** Reconstrói a partir de uma linha do banco. */
    public static function hidratar(array $linha): self
    {
        return new self(
            (int) $linha['id'],
            $linha['marca'],
            $linha['modelo'],
            (int) $linha['ano'],
            $linha['placa'],
            (float) $linha['valor_diaria'],
            StatusCarro::from($linha['status'])
        );
    }

    public function getId(): ?int { return $this->id; }
    public function getMarca(): string { return $this->marca; }
    public function getModelo(): string { return $this->modelo; }
    public function getAno(): int { return $this->ano; }
    public function getPlaca(): string { return $this->placa; }
    public function getValorDiaria(): float { return $this->valorDiaria; }
    public function getStatus(): StatusCarro { return $this->status; }

    public function getNomeCompleto(): string
    {
        return $this->marca . ' ' . $this->modelo;
    }

    public function estaDisponivel(): bool
    {
        return $this->status === StatusCarro::Disponivel;
    }

    public function calcularValor(int $dias): float
    {
        return round($this->valorDiaria * $dias, 2);
    }

    public function marcarComoAlugado(): void
    {
        if (!$this->estaDisponivel()) {
            throw new RegraDeNegocioException('Esse carro não está mais disponível. Escolha outro.');
        }
        $this->status = StatusCarro::Alugado;
    }

    /** Devolução: só um carro alugado volta a ficar disponível. */
    public function liberar(): void
    {
        if ($this->status === StatusCarro::Alugado) {
            $this->status = StatusCarro::Disponivel;
        }
    }

    public function podeSerExcluido(): bool
    {
        return $this->status !== StatusCarro::Alugado;
    }
}
