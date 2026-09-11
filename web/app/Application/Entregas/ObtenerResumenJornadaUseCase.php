<?php

namespace App\Application\Entregas;

use App\Domain\Entregas\Entrega;
use App\Domain\Entregas\EntregaRepositoryInterface;

final class ObtenerResumenJornadaUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
    ) {}

    /** @return array{litros: float, entregas: int, recientes: list<Entrega>} */
    public function ejecutar(int $jornadaId, int $limiteRecientes = 10): array
    {
        $resumen = $this->entregas->resumenDelDia($jornadaId);

        return [
            'litros' => $resumen['litros'],
            'entregas' => $resumen['entregas'],
            'recientes' => $this->entregas->recientesPorJornada($jornadaId, $limiteRecientes),
        ];
    }
}
