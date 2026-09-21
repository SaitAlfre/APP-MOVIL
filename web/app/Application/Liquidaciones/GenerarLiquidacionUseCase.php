<?php

namespace App\Application\Liquidaciones;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\Liquidacion;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Liquidacion as LiquidacionEloquent;
use App\Infrastructure\Persistence\Eloquent\Sancion;
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

        $guardada = $this->liquidaciones->guardar($liquidacion);
        $sanciones = Sancion::where('proveedor_id', $proveedorId)
            ->where('estado', 'aprobada')
            ->whereNull('aplicada_liquidacion_id')
            ->lockForUpdate()
            ->get();
        $descuento = min((float) $sanciones->sum('descuento'), $guardada->montoTotal);

        if ($descuento > 0 && $guardada->id !== null) {
            LiquidacionEloquent::whereKey($guardada->id)->update([
                'descuento_sanciones' => $descuento,
                'monto_total' => round($guardada->montoTotal - $descuento, 2),
            ]);
            Sancion::whereKey($sanciones->pluck('id')->all())->update(['aplicada_liquidacion_id' => $guardada->id]);
        }

        return $this->liquidaciones->buscarPorId($guardada->id) ?? $guardada;
    }
}
