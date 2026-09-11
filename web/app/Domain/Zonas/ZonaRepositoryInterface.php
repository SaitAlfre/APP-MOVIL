<?php

namespace App\Domain\Zonas;

interface ZonaRepositoryInterface
{
    /** @return list<Zona> */
    public function activas(): array;

    /** @return list<Zona> */
    public function todas(): array;

    public function buscarPorId(int $id): ?Zona;
}
