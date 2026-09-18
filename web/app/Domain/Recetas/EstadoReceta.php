<?php

namespace App\Domain\Recetas;

enum EstadoReceta: string
{
    case Borrador = 'borrador';
    case Activa = 'activa';
    case Archivada = 'archivada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Activa => 'Activa',
            self::Archivada => 'Archivada',
        };
    }
}
