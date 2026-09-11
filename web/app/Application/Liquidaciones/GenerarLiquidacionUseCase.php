<?php

namespace App\Application\Liquidaciones;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\Liquidacion;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

/** El monto se calcula automáticamente: litros entregados (no anulados) en el periodo × precio por litro. */
final class GenerarLiquidacionUseCase
{
    public function __construct(
        private readonly LiquidacionRepositoryInterface $liquidaciones,
        private readonly EntregaRepositoryInterface $entregas,
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(int $proveedorId, DateTimeImmutable $periodoInicio, DateTimeImmutable $periodoFin, float $precioLitro): Liquidacion
    {
        if ($this->proveedores->buscarPorId($proveedorId) === null) {
            throw new RuntimeException('El proveedor no existe.');
        }

        $litrosTotales = $this->entregas->litrosPorProveedorEnRango($proveedorId, $periodoInicio, $periodoFin);

        $liquidacion = Liquidacion::generar(
            proveedorId: $proveedorId,
            periodoInicio: $periodoInicio,
            periodoFin: $periodoFin,
            litrosTotales: $litrosTotales,
            precioLitro: $precioLitro,
            generadaEn: new DateTimeImmutable,
        );

        return $this->liquidaciones->guardar($liquidacion);
    }
}
