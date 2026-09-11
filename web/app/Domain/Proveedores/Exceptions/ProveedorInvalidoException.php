<?php

namespace App\Domain\Proveedores\Exceptions;

use DomainException;

final class ProveedorInvalidoException extends DomainException
{
    public static function codigoVacio(): self
    {
        return new self('El código del proveedor es obligatorio.');
    }

    public static function nombresVacios(): self
    {
        return new self('Los nombres son obligatorios.');
    }

    public static function dniVacio(): self
    {
        return new self('El DNI es obligatorio.');
    }

    public static function tachosInvalidos(): self
    {
        return new self('La cantidad de tachos debe ser mayor a 0.');
    }

    public static function capacidadInvalida(): self
    {
        return new self('La capacidad por tacho debe ser mayor a 0.');
    }

    public static function codigoDuplicado(): self
    {
        return new self('Ya existe un proveedor con ese código.');
    }

    public static function dniDuplicado(): self
    {
        return new self('Ya existe un proveedor con ese DNI.');
    }

    public static function zonaInexistente(): self
    {
        return new self('La zona seleccionada no existe.');
    }
}
