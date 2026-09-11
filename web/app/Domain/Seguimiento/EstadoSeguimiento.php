<?php

namespace App\Domain\Seguimiento;

enum EstadoSeguimiento: string
{
    case Inactivo = 'inactivo';
    case Buscando = 'buscando';
    case Activo = 'activo';
    case PermisoDenegado = 'permiso_denegado';
    case NoDisponible = 'no_disponible';
}
