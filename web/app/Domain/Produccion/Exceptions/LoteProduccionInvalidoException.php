<?php

namespace App\Domain\Produccion\Exceptions;

use DomainException;

final class LoteProduccionInvalidoException extends DomainException
{
    public static function codigoVacio(): self
    {
        return new self('El código del lote es obligatorio.');
    }

    public static function productoVacio(): self
    {
        return new self('El producto es obligatorio.');
    }

    public static function litrosInvalidos(): self
    {
        return new self('Los litros utilizados deben ser mayores a 0.');
    }

    public static function codigoDuplicado(): self
    {
        return new self('Ya existe un lote con ese código.');
    }

    public static function yaCerrado(): self
    {
        return new self('Este lote ya está cerrado.');
    }
}
