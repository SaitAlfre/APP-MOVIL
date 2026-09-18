<?php

namespace App\Domain\Recetas\Exceptions;

use DomainException;

final class RecetaInvalidaException extends DomainException
{
    public static function nombreVacio(): self
    {
        return new self('El nombre de la receta es obligatorio.');
    }

    public static function rendimientoInvalido(): self
    {
        return new self('El rendimiento base debe ser mayor a 0.');
    }

    public static function rendimientoUnidadVacia(): self
    {
        return new self('La unidad del rendimiento es obligatoria.');
    }

    public static function sinIngredientes(): self
    {
        return new self('La receta debe tener al menos un ingrediente.');
    }

    public static function ingredienteCantidadInvalida(): self
    {
        return new self('La cantidad de cada ingrediente debe ser mayor a 0.');
    }

    public static function ingredienteDuplicado(): self
    {
        return new self('No puedes agregar el mismo insumo dos veces en una receta.');
    }

    public static function noExiste(): self
    {
        return new self('La receta no existe.');
    }

    public static function noEditable(): self
    {
        return new self('Esta receta ya fue utilizada o está activa/archivada: edítala para crear una nueva versión.');
    }

    public static function noPuedeActivarseArchivada(): self
    {
        return new self('No puedes activar una receta archivada. Crea una nueva versión.');
    }

    public static function productoInvalido(): self
    {
        return new self('El producto seleccionado no existe.');
    }
}
