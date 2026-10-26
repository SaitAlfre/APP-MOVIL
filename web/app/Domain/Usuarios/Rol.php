<?php

namespace App\Domain\Usuarios;

enum Rol: string
{
    case Acopiador = 'acopiador';
    case Calidad = 'calidad';
    case Admin = 'admin';
    case Proveedor = 'proveedor';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Acopiador => 'Acopiador',
            self::Calidad => 'Calidad',
            self::Admin => 'Administrador',
            self::Proveedor => 'Proveedor',
        };
    }

    /** El acopiador accede a sus mapas; los demás módulos aplican la matriz de permisos. */
    public function accesoWeb(): bool
    {
        return in_array($this, [self::Admin, self::Calidad, self::Acopiador], true);
    }

    public function accesoMovil(): bool
    {
        return true;
    }

    /** @return list<self> */
    public static function rolesWeb(): array
    {
        return array_values(array_filter(self::cases(), fn (self $rol) => $rol->accesoWeb()));
    }
}
