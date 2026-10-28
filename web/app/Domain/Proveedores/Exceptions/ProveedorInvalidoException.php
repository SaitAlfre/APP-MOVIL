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
        return new self('La cantidad de tachos debe estar entre 1 y 1000.');
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

    public static function usuarioSinRolProveedor(): self
    {
        return new self('El usuario seleccionado no tiene el rol PROVEEDOR.');
    }

    public static function usuarioYaVinculado(): self
    {
        return new self('Ese usuario ya está vinculado a otro proveedor.');
    }

    public static function dniNoCoincideConCuenta(): self
    {
        return new self('El DNI de la cuenta no coincide con el de la ficha. Corrige el documento antes de vincularlas.');
    }

    public static function fichaYaVinculada(): self
    {
        return new self('Esta ficha ya tiene una cuenta vinculada. Desvincúlala antes de asociar otra.');
    }

    public static function dniBloqueadoPorCuentaVinculada(): self
    {
        return new self('La ficha está vinculada a una cuenta con este DNI. Para cambiar el documento, desvincula la cuenta o corrige ambos registros.');
    }
}
