<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusCarro: string
{
    case Disponivel = 'disponivel';
    case Alugado = 'alugado';
    case Manutencao = 'manutencao';

    public function rotulo(): string
    {
        return match ($this) {
            self::Disponivel => 'Disponível',
            self::Alugado => 'Alugado',
            self::Manutencao => 'Manutenção',
        };
    }

    /** Classe CSS da bolinha colorida (já existe no admin.css). */
    public function classeCss(): string
    {
        return match ($this) {
            self::Disponivel => 'available',
            self::Alugado => 'rental',
            self::Manutencao => 'maintenance',
        };
    }
}
