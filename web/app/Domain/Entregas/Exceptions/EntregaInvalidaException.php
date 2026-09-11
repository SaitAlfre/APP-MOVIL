<?php

namespace App\Domain\Entregas\Exceptions;

use App\Domain\Proveedores\EstadoProveedor;
use DomainException;

final class EntregaInvalidaException extends DomainException
{
    public static function litrosNoPositivos(): self
    {
        return new self('La cantidad debe ser mayor a 0.');
    }

    public static function tachosInvalidos(): self
    {
        return new self('La cantidad de tachos debe ser mayor a 0.');
    }

    public static function motivoObligatorio(): self
    {
        return new self('Debe indicar un motivo para esta acción.');
    }

    public static function superaCapacidad(float $capacidadL): self
    {
        return new self("Supera la capacidad física del tacho: {$capacidadL} L");
    }

    public static function loteVacio(): self
    {
        return new self('El lote debe tener al menos una entrega.');
    }

    public static function proveedorNoActivo(EstadoProveedor $estado): self
    {
        return new self("No se puede registrar una entrega: el proveedor está {$estado->value}.");
    }
}
