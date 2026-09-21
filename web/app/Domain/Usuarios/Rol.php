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

    /** Roles con permiso para entrar al panel web. Acopiador, Proveedor, Asistente y Despacho son exclusivos de la app móvil. */
    public function accesoWeb(): bool
    {
        return in_array($this, [self::Admin, self::Recepcion, self::Calidad, self::Produccion, self::Liquidaciones, self::Consulta], true);
    }

    /** @return list<self> */
    public static function rolesWeb(): array
    {
        return array_values(array_filter(self::cases(), fn (self $rol) => $rol->accesoWeb()));
    }
}
