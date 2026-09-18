<?php

namespace App\Application\Produccion;

use App\Domain\Inventario\Insumo;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;

final class ObtenerResumenProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
        private readonly InsumoRepositoryInterface $insumos,
    ) {}

    /** @return array{borradores: int, en_proceso: int, finalizados_periodo: int, cantidad_producida_periodo: float, stock_bajo: list<Insumo>} */
    public function ejecutar(?DateTimeImmutable $desde = null, ?DateTimeImmutable $hasta = null): array
    {
        $hasta ??= new DateTimeImmutable;
        $desde ??= $hasta->modify('-7 days');

        return [
            ...$this->lotes->resumen($desde, $hasta),
            'stock_bajo' => $this->insumos->conStockBajo(),
        ];
    }
}
