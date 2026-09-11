<?php

namespace App\Domain\Produccion;

enum EstadoLoteProduccion: string
{
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
        };
    }
}
