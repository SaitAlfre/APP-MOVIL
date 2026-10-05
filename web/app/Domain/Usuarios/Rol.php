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
    case Recepcion = 'recepcion';
    case Liquidaciones = 'liquidaciones';
    case Consulta = 'consulta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Acopiador => 'Acopiador',
            self::Calidad => 'Calidad',
            self::Admin => 'Administrador',
            self::Asistente => 'Asistente',
            self::Produccion => 'Producción',
            self::Despacho => 'Despacho',
            self::Proveedor => 'Proveedor',
            self::Recepcion => 'Recepción',
            self::Liquidaciones => 'Liquidaciones',
            self::Consulta => 'Consulta',
        };
    }

    /** El acopiador accede a sus mapas; los demás módulos aplican la matriz de permisos. */
    public function accesoWeb(): bool
    {
        return in_array($this, [self::Admin, self::Recepcion, self::Calidad, self::Produccion, self::Liquidaciones, self::Consulta, self::Acopiador], true);
    }

    /** @return list<self> */
    public static function rolesWeb(): array
    {
        return array_values(array_filter(self::cases(), fn (self $rol) => $rol->accesoWeb()));
    }
}
