<?php

namespace App\Application\Recetas;

use App\Domain\Recetas\Exceptions\RecetaInvalidaException;
use App\Domain\Recetas\Receta;
use App\Domain\Recetas\RecetaRepositoryInterface;

final class ActivarRecetaUseCase
{
    public function __construct(
        private readonly RecetaRepositoryInterface $recetas,
    ) {}

    public function ejecutar(int $recetaId): Receta
    {
        if ($this->recetas->buscarPorId($recetaId) === null) {
            throw RecetaInvalidaException::noExiste();
        }

        return $this->recetas->activar($recetaId);
    }
}
