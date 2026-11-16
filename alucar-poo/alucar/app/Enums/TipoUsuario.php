<?php

declare(strict_types=1);

namespace App\Enums;

/** Os 3 perfis possíveis. Um enum impede valores inventados como 'adm' ou 'Admin'. */
enum TipoUsuario: string
{
    case Cliente = 'cliente';
    case Vendedor = 'vendedor';
    case Admin = 'admin';

    public function rotulo(): string
    {
        return ucfirst($this->value);
    }
}
