<?php

namespace App\Application\Produccion;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ObtenerHistorialProduccionUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    /** @return array{litros_acopiados: float, unidades_producidas: int, eficiencia_litros_por_unidad: ?float, lotes: LengthAwarePaginator} */
    public function ejecutar(int $porPagina = 20): array
    {
        $litrosAcopiados = $this->entregas->litrosAprobadosTotal();
        $unidadesProducidas = $this->lotes->sumUnidadesProducidas();
        $litrosUsados = $this->lotes->sumLitrosUsados();

        return [
            'litros_acopiados' => $litrosAcopiados,
            'unidades_producidas' => $unidadesProducidas,
            'eficiencia_litros_por_unidad' => $unidadesProducidas > 0 ? round($litrosUsados / $unidadesProducidas, 3) : null,
            'lotes' => $this->lotes->paginar($porPagina),
        ];
    }
}
