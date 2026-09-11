<?php

namespace App\Domain\Calidad;

use Illuminate\Pagination\LengthAwarePaginator;

interface ControlCalidadRepositoryInterface
{
    public function paginar(int $porPagina = 20, ?EstadoCalidad $resultado = null): LengthAwarePaginator;

    public function buscarPorEntregaId(int $entregaId): ?ControlCalidad;

    public function guardar(ControlCalidad $control): ControlCalidad;

    /** @return array{aprobado: int, observado: int, rechazado: int} */
    public function contarPorResultado(): array;
}
