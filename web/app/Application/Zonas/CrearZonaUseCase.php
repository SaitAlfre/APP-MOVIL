<?php

namespace App\Application\Zonas;

use App\Domain\Zonas\Exceptions\ZonaInvalidaException;
use App\Domain\Zonas\Zona;
use App\Domain\Zonas\ZonaRepositoryInterface;

final class CrearZonaUseCase
{
    public function __construct(
        private readonly ZonaRepositoryInterface $zonas,
    ) {}

    public function ejecutar(string $nombre): Zona
    {
        if ($this->zonas->buscarPorNombre(trim($nombre)) !== null) {
            throw ZonaInvalidaException::nombreDuplicado();
        }

        return $this->zonas->guardar(Zona::crear($nombre));
    }
}
