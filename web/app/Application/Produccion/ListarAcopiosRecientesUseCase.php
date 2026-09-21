<?php

namespace App\Application\Produccion;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;

final class ListarAcopiosRecientesUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly LoteProduccionRepositoryInterface $lotes,
        private readonly ObtenerAcopioDelDiaUseCase $obtenerAcopio,
    ) {}

    /** @return list<array{fecha: DateTimeImmutable, litros: float, yaProducido: bool}> */
    public function ejecutar(int $dias = 14): array
    {
        $hasta = new DateTimeImmutable('today');
        $desde = $hasta->modify('-'.($dias - 1).' days');

        return collect($this->entregas->litrosAprobadosPorDiaEnRango($desde, $hasta))
            ->map(fn ($fila) => [
                'fecha' => new DateTimeImmutable($fila['fecha']),
                'litros' => $this->obtenerAcopio->ejecutar(new DateTimeImmutable($fila['fecha']))->litrosTotal(),
                'yaProducido' => $this->lotes->existeAsignacionEnFecha(new DateTimeImmutable($fila['fecha'])),
            ])
            ->all();
    }
}
