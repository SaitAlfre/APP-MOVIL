<?php

namespace App\Application\Usuarios;

use Illuminate\Support\Str;

final class CamposIdentidadFormulario
{
    /** @return array<string, string> */
    public static function reconocer(array $campos): array
    {
        $identidad = [];
        $alias = [
            'dni' => ['dni', 'documento', 'numero de dni', 'numero de documento', 'documento de identidad'],
            'nombres' => ['nombre', 'nombres', 'nombre completo', 'nombres completos', 'nombres y apellidos', 'nombre y apellido', 'apellidos y nombres'],
        ];
        foreach ($campos as $clave => $campo) {
            $candidatos = [Str::lower(Str::ascii(str_replace('_', ' ', $clave))), Str::lower(Str::ascii(trim($campo['etiqueta'] ?? '')))];
            foreach ($alias as $destino => $etiquetas) {
                if (array_intersect($candidatos, $etiquetas)) {
                    $identidad[$clave] = $destino;
                    break;
                }
            }
        }

        return $identidad;
    }
}
