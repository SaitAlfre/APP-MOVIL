<?php

namespace App\Domain\Liquidaciones\Exceptions;

use DomainException;

final class LiquidacionInvalidaException extends DomainException
{
    public static function periodoInvalido(): self
    {
        return new self('La fecha final del periodo debe ser posterior o igual a la inicial.');
    }

    public static function precioInvalido(): self
    {
        return new self('El precio por litro debe ser mayor a 0.');
    }

    public static function sinEntregas(): self
    {
        return new self('El proveedor no tiene entregas registradas en ese periodo.');
    }

    public static function yaPagada(): self
    {
        return new self('Esta liquidación ya fue marcada como pagada.');
    }
}
