<?php

namespace App\Domain\Calidad;

enum EstadoCalidad: string
{
    case Aprobado = 'aprobado';
    case Observado = 'observado';
    case Rechazado = 'rechazado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Aprobado => 'Aprobado',
            self::Observado => 'Observado',
            self::Rechazado => 'Rechazado',
        };
    }
}
