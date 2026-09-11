<?php

namespace App\Domain\Vehiculos\Exceptions;

use DomainException;

final class VehiculoInvalidoException extends DomainException
{
    public static function nombreVacio(): self
    {
        return new self('El nombre del vehículo es obligatorio.');
    }

    public static function placaVacia(): self
    {
        return new self('La placa es obligatoria.');
    }

    public static function placaDuplicada(): self
    {
        return new self('Ya existe un vehículo con esa placa.');
    }
}
