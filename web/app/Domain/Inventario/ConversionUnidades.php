<?php

namespace App\Domain\Inventario;

use App\Domain\Inventario\Exceptions\UnidadesIncompatiblesException;

/**
 * Controla qué unidades pueden mezclarse en cálculos de inventario y recetas.
 * Solo masa (kg/g) y volumen (L/ml) tienen una regla de conversión explícita;
 * cualquier otra unidad (unidad, caja, paquete, etc.) solo es compatible consigo misma.
 */
final class ConversionUnidades
{
    /** @var array<string, float> Factor de cada unidad hacia la unidad base de su categoría. */
    private const FACTORES = [
        'kg' => 1.0,
        'g' => 0.001,
        'l' => 1.0,
        'ml' => 0.001,
    ];

    private const CATEGORIA_MASA = 'masa';

    private const CATEGORIA_VOLUMEN = 'volumen';

    public static function normalizar(string $unidad): string
    {
        return strtolower(trim($unidad));
    }

    public static function categoria(string $unidad): string
    {
        return match (self::normalizar($unidad)) {
            'kg', 'g' => self::CATEGORIA_MASA,
            'l', 'ml' => self::CATEGORIA_VOLUMEN,
            default => 'conteo:'.self::normalizar($unidad),
        };
    }

    public static function sonCompatibles(string $unidadA, string $unidadB): bool
    {
        return self::categoria($unidadA) === self::categoria($unidadB);
    }

    public static function convertir(float $cantidad, string $de, string $a): float
    {
        $de = self::normalizar($de);
        $a = self::normalizar($a);

        if ($de === $a) {
            return $cantidad;
        }

        if (! self::sonCompatibles($de, $a)) {
            throw UnidadesIncompatiblesException::paraUnidades($de, $a);
        }

        $enBase = $cantidad * self::FACTORES[$de];

        return $enBase / self::FACTORES[$a];
    }
}
