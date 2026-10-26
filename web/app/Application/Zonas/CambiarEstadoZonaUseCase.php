<?php

namespace App\Application\Zonas;

use App\Domain\Zonas\Zona;
use App\Domain\Zonas\ZonaRepositoryInterface;
use RuntimeException;

final class CambiarEstadoZonaUseCase
{
    public function __construct(
        private readonly ZonaRepositoryInterface $zonas,
    ) {}

    public function ejecutar(int $zonaId, bool $activo): Zona
    {
        $zona = $this->zonas->buscarPorId($zonaId);

        if ($zona === null) {
            throw new RuntimeException('La zona no existe.');
        }

        if (! $activo) {
            if ($this->zonas->tieneProveedoresActivos($zonaId)) {
                throw new RuntimeException('No se puede desactivar: hay proveedores activos en esta zona.');
            }
            if ($this->zonas->tieneJornadasAbiertas($zonaId)) {
                throw new RuntimeException('No se puede desactivar: hay una jornada abierta en esta zona.');
            }
            if ($this->zonas->tieneCuentasAsignadas($zonaId)) {
                throw new RuntimeException('No se puede desactivar: hay cuentas con esta zona asignada. Reasígnalas en Usuarios y roles.');
            }
        }

        return $this->zonas->guardar($zona->conEstado($activo));
    }
}
