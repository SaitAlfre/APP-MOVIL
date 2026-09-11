<?php

namespace App\Domain\Liquidaciones;

enum EstadoLiquidacion: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagada => 'Pagada',
        };
    }
}
