<?php

namespace App\Application\Calidad;

use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Calidad\EstadoCalidad;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarControlesCalidadUseCase
{
    public function __construct(
        private readonly ControlCalidadRepositoryInterface $controles,
    ) {}

    public function ejecutar(int $porPagina = 20, ?EstadoCalidad $resultado = null): LengthAwarePaginator
    {
        return $this->controles->paginar($porPagina, $resultado);
    }
}
