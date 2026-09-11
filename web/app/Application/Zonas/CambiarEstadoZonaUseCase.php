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

        return $this->zonas->guardar($zona->conEstado($activo));
    }
}
