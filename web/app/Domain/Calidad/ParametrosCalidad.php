<?php

namespace App\Domain\Calidad;

/**
 * Parámetros del análisis LactoScan, idénticos a los de la app móvil (shared/.../calidad/ParametrosCalidad.kt):
 * mismas claves, rangos y regla de estado, para que un análisis se califique igual en el celular y en el panel.
 * Referencias iniciales del proyecto; no constituyen una certificación de calidad.
 */
final class ParametrosCalidad
{
    /** clave => [nombre, unidad, mínimo, máximo, ejemplo, columna en analisis_calidad] */
    public const PARAMETROS = [
        'temperatura' => ['Temperatura', '°C', 0.0, 8.0, '6.0', 'temperatura'],
        'grasa' => ['Grasa', '%', 3.0, 6.0, '3.5', 'grasa'],
        'sng' => ['Sólidos no grasos', '%', 8.2, 10.0, '8.7', 'sng'],
        'densidad' => ['Densidad', 'g/cm³', 1.028, 1.034, '1.030', 'densidad'],
        'proteina' => ['Proteína', '%', 2.8, 4.2, '3.2', 'proteina'],
        'lactosa' => ['Lactosa', '%', 4.2, 5.5, '4.7', 'lactosa'],
        'sales' => ['Sales', '%', 0.6, 0.85, '0.70', 'sales'],
        'solidos' => ['Sólidos totales', '%', 11.2, 16.0, '12.2', 'solidos_totales'],
        'agua' => ['Agua añadida', '%', 0.0, 0.0, '0.0', 'agua_anadida'],
        'congelacion' => ['Punto de congelación', '°C', -0.555, -0.515, '-0.530', 'punto_congelacion'],
        'ph' => ['pH', '', 6.5, 6.8, '6.7', 'ph'],
    ];

    public static function nombre(string $clave): string
    {
        return self::PARAMETROS[$clave][0];
    }

    public static function unidad(string $clave): string
    {
        return self::PARAMETROS[$clave][1];
    }

    public static function columna(string $clave): string
    {
        return self::PARAMETROS[$clave][5];
    }

    /** Igual que ParametroCalidad.referencia en la app: "0.0 a 8.0 °C", o el valor único. */
    public static function referencia(string $clave, string $unidadCongelacion = '°C'): string
    {
        if ($clave === 'congelacion' && $unidadCongelacion === '°H') {
            return 'Pendiente de configurar en °H';
        }
        [, $unidad, $minimo, $maximo] = self::PARAMETROS[$clave];
        $texto = $minimo === $maximo ? self::numero($minimo) : self::numero($minimo).' a '.self::numero($maximo);

        return trim($texto.' '.$unidad);
    }

    /** Único parámetro que admite negativos; en el resto el signo es un error de formato. */
    public static function permiteNegativo(string $clave): bool
    {
        return $clave === 'congelacion';
    }

    public static function correcto(string $clave, ?float $valor, string $unidadCongelacion = '°C'): bool
    {
        if ($valor === null || ! is_finite($valor)) {
            return false;
        }
        if ($clave === 'congelacion' && $unidadCongelacion !== '°C') {
            return false;
        }
        [, , $minimo, $maximo] = self::PARAMETROS[$clave];

        return $valor >= $minimo && $valor <= $maximo;
    }

    /**
     * Misma evaluación que BorradorVisita en la app: agua añadida > 0 = RECHAZADO; cualquier parámetro medido
     * fuera de rango = OBSERVADO; si no, APROBADO. Los parámetros vacíos no cuentan como fallidos.
     *
     * @param  array<string, float|null>  $valores  por clave de parámetro
     * @return array{estado: EstadoAnalisis, alertas: list<string>, alertados: list<string>, referencias: array<string, string>, correctos: int}
     */
    public static function evaluar(array $valores, string $unidadCongelacion = '°C'): array
    {
        $referencias = [];
        $alertados = [];
        $alertas = [];
        foreach (array_keys(self::PARAMETROS) as $clave) {
            $referencias[$clave] = self::referencia($clave, $unidadCongelacion);
            $valor = $valores[$clave] ?? null;
            if ($valor !== null && ! self::correcto($clave, $valor, $unidadCongelacion)) {
                $alertados[] = $clave;
                $alertas[] = self::nombre($clave).': '.self::numero($valor).' · Referencia: '.$referencias[$clave];
            }
        }

        $estado = match (true) {
            ($valores['agua'] ?? null) !== null && $valores['agua'] > 0 => EstadoAnalisis::Rechazado,
            $alertados !== [] => EstadoAnalisis::Observado,
            default => EstadoAnalisis::Aprobado,
        };

        return [
            'estado' => $estado, 'alertas' => $alertas, 'alertados' => $alertados, 'referencias' => $referencias,
            'correctos' => count(self::PARAMETROS) - count($alertados),
        ];
    }

    /** Como Kotlin imprime un Double: sin ceros de más, pero con al menos un decimal (8.0, 1.028, -0.53). */
    public static function numero(float $valor): string
    {
        $texto = rtrim(rtrim(number_format($valor, 4, '.', ''), '0'), '.');

        return str_contains($texto, '.') ? $texto : $texto.'.0';
    }
}
