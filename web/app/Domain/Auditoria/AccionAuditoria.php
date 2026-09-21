<?php

namespace App\Domain\Auditoria;

enum AccionAuditoria: string
{
    case Crear = 'crear';
    case Actualizar = 'actualizar';
    case Desactivar = 'desactivar';
    case Corregir = 'corregir';
    case Anular = 'anular';
    case Autorizar = 'autorizar';
    case Rechazar = 'rechazar';
    case IniciarSesion = 'iniciar_sesion';
    case CerrarSesion = 'cerrar_sesion';
    case AccesoFallido = 'acceso_fallido';
}
