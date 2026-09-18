<?php

namespace App\Domain\Produccion;

enum EstadoLoteProduccion: string
{
    case Borrador = 'borrador';
    case EnProceso = 'en_proceso';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnProceso => 'En proceso',
            self::Finalizado => 'Finalizado',
            self::Cancelado => 'Cancelado',
        };
    }
}
