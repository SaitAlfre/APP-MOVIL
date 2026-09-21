<?php

namespace App\Domain\Produccion\Exceptions;

use App\Domain\Produccion\EstadoLoteProduccion;
use DomainException;

final class LoteProduccionInvalidoException extends DomainException
{
    public static function productoNoExiste(): self
    {
        return new self('La receta seleccionada no existe.');
    }

    public static function litrosAsignadosInvalidos(): self
    {
        return new self('Los litros asignados deben ser mayores a 0.');
    }

    public static function superaSaldoDisponible(float $disponibleL): self
    {
        return new self("No puedes asignar más leche de la disponible ese día: {$disponibleL} L.");
    }

    public static function transicionInvalida(EstadoLoteProduccion $actual, string $accion): self
    {
        return new self("No se puede {$accion} un lote en estado \"{$actual->etiqueta()}\".");
    }

    public static function consumoSuperaAsignado(float $asignadoL): self
    {
        return new self("Los litros usados más la merma de proceso no pueden superar lo asignado: {$asignadoL} L.");
    }

    public static function litrosUsadosInvalidos(): self
    {
        return new self('Los litros usados no pueden ser negativos.');
    }

    public static function motivoCancelacionObligatorio(): self
    {
        return new self('Debes indicar el motivo de la cancelación.');
    }
}
