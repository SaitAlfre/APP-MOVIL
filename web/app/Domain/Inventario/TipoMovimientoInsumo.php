<?php

namespace App\Domain\Inventario;

enum TipoMovimientoInsumo: string
{
    case Entrada = 'entrada';
    case Reserva = 'reserva';
    case Consumo = 'consumo';
    case Liberacion = 'liberacion';
    case Ajuste = 'ajuste';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Reserva => 'Reserva',
            self::Consumo => 'Consumo',
            self::Liberacion => 'Liberación',
            self::Ajuste => 'Ajuste',
        };
    }
}
