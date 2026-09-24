<?php

namespace App\Domain\Calidad;

/** Estado de un análisis LactoScan, con los mismos valores que la app móvil (EstadoControlCalidad). */
enum EstadoAnalisis: string
{
    case Aprobado = 'APROBADO';
    case Observado = 'OBSERVADO';
    case Rechazado = 'RECHAZADO';
    case Repetir = 'REPETIR';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Aprobado => 'Aprobado',
            self::Observado => 'Observado',
            self::Rechazado => 'Rechazado',
            self::Repetir => 'Repetir prueba',
        };
    }

    /** Resultado del control por entrega que usan producción, recepción y sanciones. */
    public function resultadoEntrega(): EstadoCalidad
    {
        return match ($this) {
            self::Aprobado => EstadoCalidad::Aprobado,
            self::Rechazado => EstadoCalidad::Rechazado,
            self::Observado, self::Repetir => EstadoCalidad::Observado,
        };
    }
}
