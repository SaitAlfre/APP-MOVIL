<?php

namespace App\Application\Proveedores;

/** Estado de un DNI frente a las fichas productivas y las cuentas de acceso antes de registrar un proveedor. */
enum SituacionRegistroProveedor: string
{
    case Nuevo = 'nuevo';
    case FichaSinCuenta = 'ficha_sin_cuenta';
    case CuentaSinFicha = 'cuenta_sin_ficha';
    case FichaYCuentaSinVincular = 'ficha_y_cuenta_sin_vincular';
    case YaVinculado = 'ya_vinculado';
    case CuentaVinculadaAOtraFicha = 'cuenta_vinculada_a_otra_ficha';
    case ConflictoDni = 'conflicto_dni';
    case CuentaDeOtroRol = 'cuenta_de_otro_rol';

    /** Acción que el administrador debe confirmar de forma explícita; null cuando no hace falta (nuevo) o no se permite (bloqueos). */
    public function accionConfirmable(): ?string
    {
        return match ($this) {
            self::FichaSinCuenta => 'vincular_ficha',
            self::CuentaSinFicha => 'asociar_cuenta',
            self::FichaYCuentaSinVincular => 'vincular_existentes',
            default => null,
        };
    }

    public function bloqueaRegistro(): bool
    {
        return in_array($this, [self::YaVinculado, self::CuentaVinculadaAOtraFicha, self::ConflictoDni, self::CuentaDeOtroRol], true);
    }

    public function mensaje(): string
    {
        return match ($this) {
            self::Nuevo => 'DNI disponible. Se registrará una ficha nueva.',
            self::FichaSinCuenta => 'Este DNI ya tiene una ficha productiva sin cuenta. Puedes crear su cuenta móvil y vincularla; se conservan su código, QR, zona y entregas.',
            self::CuentaSinFicha => 'Este DNI ya tiene una cuenta con rol Proveedor sin ficha. Se registrará la ficha productiva y se asociará a esa cuenta, sin crear otra.',
            self::FichaYCuentaSinVincular => 'Este DNI ya tiene una ficha y una cuenta de proveedor sin vincular. Puedes vincularlas sin modificar sus datos.',
            self::YaVinculado => 'Este proveedor ya está registrado y su ficha está vinculada a su cuenta. Revisa la ficha existente.',
            self::CuentaVinculadaAOtraFicha => 'La cuenta con este DNI ya está vinculada a otra ficha. No se permite una segunda vinculación.',
            self::ConflictoDni => 'Los datos de identidad no coinciden entre la ficha y la cuenta (DNI distinto o cuentas repetidas). Un administrador debe corregirlos antes de vincular.',
            self::CuentaDeOtroRol => 'Este DNI pertenece a una cuenta con otro rol. No se cambiará su rol automáticamente: revisa la cuenta en Usuarios y accesos.',
        };
    }

    public function etiquetaAccion(): ?string
    {
        return match ($this) {
            self::FichaSinCuenta => 'Usar la ficha existente y crear su cuenta',
            self::CuentaSinFicha => 'Registrar la ficha y asociarla a la cuenta existente',
            self::FichaYCuentaSinVincular => 'Vincular la ficha y la cuenta existentes',
            default => null,
        };
    }
}
