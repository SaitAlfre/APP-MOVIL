<?php

namespace App\Domain\Proveedores;

enum EstadoProveedor: string
{
    case Activo = 'activo';
    case Suspendido = 'suspendido';
    case Retirado = 'retirado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Suspendido => 'Suspendido',
            self::Retirado => 'Retirado',
        };
    }
}
