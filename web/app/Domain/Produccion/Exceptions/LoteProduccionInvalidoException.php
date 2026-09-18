<?php

namespace App\Domain\Produccion\Exceptions;

use DomainException;

final class LoteProduccionInvalidoException extends DomainException
{
    public static function codigoVacio(): self
    {
        return new self('El código del lote es obligatorio.');
    }

    public static function codigoDuplicado(): self
    {
        return new self('Ya existe un lote con ese código.');
    }

    public static function cantidadInvalida(): self
    {
        return new self('La cantidad a fabricar debe ser mayor a 0.');
    }

    public static function noExiste(): self
    {
        return new self('El lote no existe.');
    }

    public static function productoInactivo(): self
    {
        return new self('El producto está inactivo y no puede fabricarse.');
    }

    public static function sinRecetaActiva(): self
    {
        return new self('El producto no tiene una receta activa. Actívala antes de fabricar.');
    }

    public static function noEstaEnBorrador(): self
    {
        return new self('El lote ya fue iniciado, finalizado o cancelado. La acción no se pudo repetir.');
    }

    public static function noEstaEnProceso(): self
    {
        return new self('El lote no está en proceso.');
    }

    public static function noSePuedeCancelar(): self
    {
        return new self('El lote ya fue finalizado o cancelado.');
    }

    public static function motivoCancelacionObligatorio(): self
    {
        return new self('Debes indicar el motivo de la cancelación.');
    }

    public static function consumoNoInformado(string $insumo): self
    {
        return new self("Falta informar el consumo real de \"{$insumo}\" (puede ser 0, pero debe registrarse explícitamente).");
    }

    public static function consumoInvalido(): self
    {
        return new self('El consumo registrado no puede ser negativo ni mayor a lo reservado.');
    }

    public static function cantidadObtenidaInvalida(): self
    {
        return new self('La cantidad obtenida no puede ser negativa.');
    }
}
