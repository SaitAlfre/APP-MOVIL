<?php

namespace App\Application\Reportes;

use App\Domain\Entregas\EntregaRepositoryInterface;
use DateTimeImmutable;

final class ObtenerResumenGeneralUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
    ) {}

    /** @return array{litros: float, entregas: int, litrosPorZona: list<array{zona_id: int, litros: float}>} */
    public function ejecutar(int $dias = 7): array
    {
        $hasta = new DateTimeImmutable('today');
        $desde = $hasta->modify('-'.($dias - 1).' days');

        $resumen = $this->entregas->resumenPorRango($desde, $hasta);

        return [
            'litros' => $resumen['litros'],
            'entregas' => $resumen['entregas'],
            'litrosPorZona' => $this->entregas->litrosPorZonaEnRango($desde, $hasta),
        ];
    }
}
