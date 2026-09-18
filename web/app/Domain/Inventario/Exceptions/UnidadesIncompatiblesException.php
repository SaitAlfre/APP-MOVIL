<?php

namespace App\Domain\Inventario\Exceptions;

use DomainException;

final class UnidadesIncompatiblesException extends DomainException
{
    public static function paraUnidades(string $de, string $a): self
    {
        return new self("No se puede convertir de \"{$de}\" a \"{$a}\": son unidades de distinta naturaleza (masa, volumen o conteo).");
    }
}
