<?php

namespace App\Domain\Proveedores;

use DateTimeImmutable;

/**
 * Estado de la ruta del acopiador de la zona del proveedor, visto desde el propio proveedor.
 * Mismos estados que EstadoRutaAcopio.kt en el móvil (sin las variantes propias de Firestore/offline).
 */
final class EstadoRuta
{
    private function __construct(
        public readonly string $estado,
        public readonly ?float $lat = null,
        public readonly ?float $lng = null,
        public readonly ?DateTimeImmutable $capturadaEn = null,
        public readonly bool $esVivo = false,
    ) {}

    public static function sinRutaAsignada(): self
    {
        return new self('sin_ruta_asignada');
    }

    public static function jornadaNoIniciada(): self
    {
        return new self('jornada_no_iniciada');
    }

    public static function seguimientoNoActivado(): self
    {
        return new self('seguimiento_no_activado');
    }

    public static function ubicacionNoDisponible(): self
    {
        return new self('ubicacion_no_disponible');
    }

    public static function disponible(float $lat, float $lng, DateTimeImmutable $capturadaEn, bool $esVivo): self
    {
        return new self('disponible', $lat, $lng, $capturadaEn, $esVivo);
    }
}
