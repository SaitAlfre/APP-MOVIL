<?php

namespace App\Domain\Productos\Exceptions;

use DomainException;

final class ProductoInvalidoException extends DomainException
{
    public static function nombreVacio(): self
    {
        return new self('El nombre del producto es obligatorio.');
    }

    public static function presentacionVacia(): self
    {
        return new self('La presentación del producto es obligatoria.');
    }

    public static function unidadProduccionVacia(): self
    {
        return new self('La unidad de producción es obligatoria.');
    }

    public static function contenidoPorUnidadInvalido(): self
    {
        return new self('El contenido por unidad debe ser mayor a 0.');
    }

    public static function litrosPorUnidadInvalido(): self
    {
        return new self('Los litros de leche por unidad deben ser mayores a 0.');
    }

    public static function nombreDuplicado(): self
    {
        return new self('Ya existe un producto con ese nombre.');
    }

    public static function noExiste(): self
    {
        return new self('El producto no existe.');
    }
}
