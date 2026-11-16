<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/** O usuário está logado, mas o perfil dele não permite essa ação. */
class AcessoNegadoException extends DomainException
{
    public function __construct(string $mensagem = 'Você não tem permissão para essa ação.')
    {
        parent::__construct($mensagem);
    }
}
