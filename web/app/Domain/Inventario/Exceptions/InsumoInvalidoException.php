<?php

namespace App\Domain\Inventario\Exceptions;

use DomainException;

final class InsumoInvalidoException extends DomainException
{
    public static function nombreVacio(): self
    {
        return new self('El nombre del insumo es obligatorio.');
    }

    public static function unidadVacia(): self
    {
        return new self('La unidad del insumo es obligatoria.');
    }

    public static function stockMinimoInvalido(): self
    {
        return new self('El stock mínimo no puede ser negativo.');
    }

    public static function nombreDuplicado(): self
    {
        return new self('Ya existe un insumo con ese nombre.');
    }

    public static function cantidadInvalida(): self
    {
        return new self('La cantidad debe ser mayor a 0.');
    }

    public static function disponibilidadInsuficiente(string $insumo, float $necesaria, float $disponible, string $unidad): self
    {
        return new self(sprintf(
            'No hay suficiente "%s" disponible: se necesitan %s %s y solo hay %s %s disponibles.',
            $insumo,
            rtrim(rtrim(number_format($necesaria, 3, '.', ''), '0'), '.'),
            $unidad,
            rtrim(rtrim(number_format($disponible, 3, '.', ''), '0'), '.'),
            $unidad,
        ));
    }

    public static function motivoAjusteObligatorio(): self
    {
        return new self('Todo ajuste de inventario debe indicar un motivo.');
    }

    public static function noExiste(): self
    {
        return new self('El insumo no existe.');
    }
}
