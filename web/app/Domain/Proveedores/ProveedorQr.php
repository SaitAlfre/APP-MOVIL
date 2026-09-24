<?php

namespace App\Domain\Proveedores;

/**
 * El QR de un proveedor codifica únicamente su código (nunca datos sensibles como DNI o nombre), con un
 * prefijo de formato para distinguir "QR ajeno a Ecolecta" de "proveedor inexistente".
 *
 * Se usa el código porque es lo único que identifica la misma ficha en el panel y en cada celular: los
 * ids (entero aquí, UUID local en la app) son distintos en cada lado. Mismo formato que la app móvil
 * (ver domain/ProveedorQr.kt). Los QR antiguos con el id del panel se siguen leyendo.
 */
final class ProveedorQr
{
    private const string PREFIJO = 'ECOLECTA:PROVEEDOR:';

    private const string PREFIJO_CODIGO = 'ECOLECTA:PROVEEDOR:CODIGO:';

    public static function generar(string $codigo): string
    {
        return self::PREFIJO_CODIGO.$codigo;
    }

    public static function extraerCodigo(string $contenido): ?string
    {
        $valor = trim($contenido);

        if (! str_starts_with($valor, self::PREFIJO_CODIGO)) {
            return null;
        }

        $codigo = substr($valor, strlen(self::PREFIJO_CODIGO));

        return $codigo !== '' ? $codigo : null;
    }

    /** Formato anterior (id del panel): solo para leer QR ya impresos. */
    public static function extraerId(string $contenido): ?int
    {
        $valor = trim($contenido);

        if (! str_starts_with($valor, self::PREFIJO) || str_starts_with($valor, self::PREFIJO_CODIGO)) {
            return null;
        }

        $id = substr($valor, strlen(self::PREFIJO));

        return ctype_digit($id) ? (int) $id : null;
    }
}
