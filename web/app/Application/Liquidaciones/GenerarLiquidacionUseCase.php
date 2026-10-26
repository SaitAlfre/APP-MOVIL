<?php

namespace App\Application\Liquidaciones;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\Liquidacion;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Liquidacion as LiquidacionEloquent;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
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
        return DB::transaction(function () use ($proveedorId, $periodoInicio, $periodoFin, $precioLitro): Liquidacion {
            Proveedor::query()->whereKey($proveedorId)->lockForUpdate()->first();
            if ($this->proveedores->buscarPorId($proveedorId) === null) {
                throw new RuntimeException('El proveedor no existe.');
            }
            if (LiquidacionEloquent::query()->where('proveedor_id', $proveedorId)
                ->whereDate('periodo_inicio', '<=', $periodoFin->format('Y-m-d'))
                ->whereDate('periodo_fin', '>=', $periodoInicio->format('Y-m-d'))->exists()) {
                throw new RuntimeException('El proveedor ya tiene una liquidación que incluye estas fechas. Revisa el periodo para evitar pagar dos veces las mismas entregas.');
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
        });
    }
}
