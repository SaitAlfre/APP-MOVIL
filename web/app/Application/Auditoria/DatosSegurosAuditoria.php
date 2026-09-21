<?php

namespace App\Application\Auditoria;

class DatosSegurosAuditoria
{
    public static function limpiar(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $datos = json_decode($valor, true);
        if (is_array($datos)) {
            return json_encode(self::filtrar($datos), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return preg_replace('/\b(pin(?:_hash|_confirmation)?|password|contrase[nñ]a|token|secret|api_key|authorization|cookie)\s*[=:]\s*[^;\r\n]*/iu', '$1=[OCULTO]', $valor);
    }

    private static function filtrar(array $datos): array
    {
        foreach ($datos as $clave => $valor) {
            if (preg_match('/pin|password|contrase|token|secret|api.?key|authorization|cookie|session/i', (string) $clave)) {
                $datos[$clave] = '[OCULTO]';
            } elseif (is_array($valor)) {
                $datos[$clave] = self::filtrar($valor);
            } elseif (is_string($valor)) {
                $datos[$clave] = self::limpiar($valor);
            }
        }

        return $datos;
    }
}
