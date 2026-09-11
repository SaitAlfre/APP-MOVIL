<?php

namespace App\Domain\Usuarios;

enum Rol: string
{
    case Acopiador = 'acopiador';
    case Calidad = 'calidad';
    case Admin = 'admin';
    case Asistente = 'asistente';
    case Produccion = 'produccion';
    case Despacho = 'despacho';
    case Proveedor = 'proveedor';
}
