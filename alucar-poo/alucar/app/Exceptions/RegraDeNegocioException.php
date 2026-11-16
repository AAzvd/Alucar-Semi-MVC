<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Lançada quando o usuário tenta algo que as regras do sistema não permitem
 * (carro já alugado, datas inválidas, e-mail repetido...). A mensagem já é
 * amigável e pode ser mostrada na tela. Substitui os códigos ?error=indisponivel.
 */
class RegraDeNegocioException extends DomainException
{
}
