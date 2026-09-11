<?php

namespace App\Domain\Calidad\Exceptions;

use DomainException;

final class ControlCalidadInvalidoException extends DomainException
{
    public static function entregaYaEvaluada(): self
    {
        return new self('Esta entrega ya tiene un control de calidad registrado.');
    }

    public static function temperaturaFueraDeRango(): self
    {
        return new self('La temperatura debe estar entre -5°C y 60°C.');
    }

    public static function acidezFueraDeRango(): self
    {
        return new self('La acidez debe estar entre 0°D y 50°D.');
    }
}
