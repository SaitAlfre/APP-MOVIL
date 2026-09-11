<?php

namespace App\Domain\Proveedores;

/**
 * El QR de un proveedor codifica únicamente su id (nunca datos sensibles como DNI o nombre),
 * con un prefijo de formato para distinguir "QR ajeno a Ecolecta" de "proveedor inexistente".
 * Mismo formato que la app móvil (ver domain/ProveedorQr.kt).
 */
final class ProveedorQr
{
    private const string PREFIJO = 'ECOLECTA:PROVEEDOR:';

    public static function generar(int $proveedorId): string
    {
        return self::PREFIJO.$proveedorId;
    }

    public static function extraerId(string $contenido): ?int
    {
        $valor = trim($contenido);

        if (! str_starts_with($valor, self::PREFIJO)) {
            return null;
        }

        $id = substr($valor, strlen(self::PREFIJO));

        return ctype_digit($id) ? (int) $id : null;
    }
}
