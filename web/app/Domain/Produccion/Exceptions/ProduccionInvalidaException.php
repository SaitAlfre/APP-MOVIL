<?php

namespace App\Domain\Produccion\Exceptions;

use DomainException;

final class ProduccionInvalidaException extends DomainException
{
    public static function sinLitrosEseDia(): self
    {
        return new self('No hay litros de leche aprobados por Calidad para esa fecha.');
    }

    public static function yaProducidoEsaFecha(): self
    {
        return new self('Ya se ejecutó una producción con el acopio de esta fecha.');
    }

    public static function productoNoExiste(): self
    {
        return new self('La receta seleccionada no existe.');
    }
}
