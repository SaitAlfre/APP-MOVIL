<?php

namespace App\Application\Zonas;

use App\Domain\Zonas\Exceptions\ZonaInvalidaException;
use App\Domain\Zonas\Zona;
use App\Domain\Zonas\ZonaRepositoryInterface;
use RuntimeException;

final class ActualizarZonaUseCase
{
    public function __construct(
        private readonly ZonaRepositoryInterface $zonas,
    ) {}

    public function ejecutar(int $zonaId, string $nombre): Zona
    {
        $zona = $this->zonas->buscarPorId($zonaId);

        if ($zona === null) {
            throw new RuntimeException('La zona no existe.');
        }

        $existente = $this->zonas->buscarPorNombre(trim($nombre));
        if ($existente !== null && $existente->id !== $zona->id) {
            throw ZonaInvalidaException::nombreDuplicado();
        }

        return $this->zonas->guardar($zona->actualizar($nombre));
    }
}
