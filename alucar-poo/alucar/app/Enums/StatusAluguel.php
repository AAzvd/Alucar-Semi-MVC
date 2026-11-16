<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusAluguel: string
{
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    case EmAndamento = 'em_andamento';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Confirmado => 'Confirmado',
            self::EmAndamento => 'Em andamento',
            self::Finalizado => 'Finalizado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function classeCss(): string
    {
        return match ($this) {
            self::Finalizado => 'available',
            self::Cancelado => 'rental',
            default => 'maintenance',
        };
    }

    /** Aluguel "aberto": o carro ainda está com o cliente. */
    public function estaAtivo(): bool
    {
        return $this === self::Confirmado || $this === self::EmAndamento;
    }

    /** Valores em texto dos status ativos (para usar em consultas SQL). */
    public static function ativos(): array
    {
        return [self::Confirmado->value, self::EmAndamento->value];
    }
}
