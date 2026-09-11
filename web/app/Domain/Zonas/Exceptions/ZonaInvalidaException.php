<?php

namespace App\Domain\Zonas\Exceptions;

use DomainException;

final class ZonaInvalidaException extends DomainException
{
    public static function nombreVacio(): self
    {
        return new self('El nombre de la zona es obligatorio.');
    }

    public static function nombreDuplicado(): self
    {
        return new self('Ya existe una zona con ese nombre.');
    }
}
