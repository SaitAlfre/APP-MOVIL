<?php

namespace App\Application\Produccion;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;

final class ListarDiasConSaldoDisponibleUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly LoteProduccionRepositoryInterface $lotes,
        private readonly ObtenerAcopioDelDiaUseCase $obtenerAcopio,
    ) {}

    /** @return list<array{fecha: DateTimeImmutable, litrosTotal: float, litrosAsignados: float, litrosDisponibles: float}> */
    public function ejecutar(int $dias = 14): array
    {
        $hasta = new DateTimeImmutable('today');
        $desde = $hasta->modify('-'.($dias - 1).' days');

        return collect($this->entregas->litrosAprobadosPorDiaEnRango($desde, $hasta))
            ->map(function ($fila) {
                $fecha = new DateTimeImmutable($fila['fecha']);
                $litrosTotal = $this->obtenerAcopio->ejecutar($fecha)->litrosTotal();
                $litrosAsignados = $this->lotes->litrosAsignadosEnFecha($fecha);

                return [
                    'fecha' => $fecha,
                    'litrosTotal' => $litrosTotal,
                    'litrosAsignados' => $litrosAsignados,
                    'litrosDisponibles' => round($litrosTotal - $litrosAsignados, 3),
                ];
            })
            ->filter(fn (array $dia) => $dia['litrosDisponibles'] > 0.0)
            ->values()
            ->all();
    }
}
