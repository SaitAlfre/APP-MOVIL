<?php

namespace App\Application\Proveedores;

use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Domain\Proveedores\EstadoRuta;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Seguimiento\SeguimientoRepositoryInterface;
use DateTimeImmutable;

/** Igual que ObtenerRutaAcopioUseCase.kt: resuelve la zona del proveedor y observa la jornada/seguimiento del acopiador. */
final class ObtenerRutaAcopioUseCase
{
    private const int SEGUNDOS_PARA_ESTAR_VIVO = 90;

    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly JornadaRepositoryInterface $jornadas,
        private readonly SeguimientoRepositoryInterface $seguimiento,
    ) {}

    public function ejecutar(int $usuarioId): EstadoRuta
    {
        $proveedor = $this->proveedores->buscarPorUsuarioId($usuarioId);
        if ($proveedor === null) {
            return EstadoRuta::sinRutaAsignada();
        }

        $jornada = $this->jornadas->obtenerAbiertaPorZona($proveedor->zonaId);
        if ($jornada === null) {
            return EstadoRuta::jornadaNoIniciada();
        }

        if (! $jornada->seguimientoActivo) {
            return EstadoRuta::seguimientoNoActivado();
        }

        $posicion = $this->seguimiento->ultimaPosicion($jornada->id);
        if ($posicion === null) {
            return EstadoRuta::ubicacionNoDisponible();
        }

        $segundosDesdeCaptura = (new DateTimeImmutable)->getTimestamp() - $posicion->capturadaEn->getTimestamp();

        return EstadoRuta::disponible(
            lat: $posicion->lat,
            lng: $posicion->lng,
            capturadaEn: $posicion->capturadaEn,
            esVivo: $segundosDesdeCaptura < self::SEGUNDOS_PARA_ESTAR_VIVO,
        );
    }
}
