<?php

namespace App\Domain\Recepcion\Exceptions;

use DomainException;

final class RecepcionInvalidaException extends DomainException
{
    public static function litrosMedidosNegativos(): self
    {
        return new self('Los litros medidos no pueden ser negativos.');
    }

    public static function litrosRecolectadosInvalidos(): self
    {
        return new self('No hay litros recolectados para esta jornada.');
    }

    public static function motivoDiferenciaObligatorio(): self
    {
        return new self('Debe indicar el motivo de la diferencia entre lo recolectado y lo medido en planta.');
    }

    public static function jornadaSinEntregas(): self
    {
        return new self('La jornada no tiene entregas registradas todavía.');
    }

    public static function diaYaProcesado(): self
    {
        return new self('Este día ya fue procesado en Producción. No se puede registrar ni corregir la recepción.');
    }
}
