<?php

namespace App\Application\Liquidaciones;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\Liquidacion;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use App\Infrastructure\Persistence\Eloquent\Liquidacion as LiquidacionEloquent;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Valora las entregas de cada fecha con su tarifa; conserva el detalle y los precios aplicados. */
final class GenerarLiquidacionUseCase
{
    public function __construct(
        private readonly LiquidacionRepositoryInterface $liquidaciones,
        private readonly EntregaRepositoryInterface $entregas,
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(int $proveedorId, DateTimeImmutable $periodoInicio, DateTimeImmutable $periodoFin, ?float $precioLitro = null): Liquidacion
    {
        return DB::transaction(function () use ($proveedorId, $periodoInicio, $periodoFin, $precioLitro): Liquidacion {
            Configuracion::whereKey('tarifas_version')->lockForUpdate()->firstOrFail();
            Proveedor::query()->whereKey($proveedorId)->lockForUpdate()->first();
            if ($this->proveedores->buscarPorId($proveedorId) === null) {
                throw new RuntimeException('El proveedor no existe.');
            }
            if (LiquidacionEloquent::query()->where('proveedor_id', $proveedorId)
                ->whereDate('periodo_inicio', '<=', $periodoFin->format('Y-m-d'))
                ->whereDate('periodo_fin', '>=', $periodoInicio->format('Y-m-d'))->exists()) {
                throw new RuntimeException('El proveedor ya tiene una liquidación que incluye estas fechas. Revisa el periodo para evitar pagar dos veces las mismas entregas.');
            }

            $calcular = app(CalcularImporteTemporadas::class);
            $tarifas = $calcular->tarifas($periodoInicio, $periodoFin);
            // El precio explícito se conserva solo para importaciones históricas internas.
            if ($tarifas === [] && $precioLitro !== null && ! TarifaLeche::exists()) {
                $tarifas = [['desde' => $periodoInicio->format('Y-m-d'), 'hasta' => $periodoFin->format('Y-m-d'), 'precio' => $precioLitro]];
            }
            $valorado = $calcular->calcular($proveedorId, $periodoInicio, $periodoFin, $tarifas);
            $litrosTotales = $valorado['litros'];
            $precioLitro = $valorado['precio'];

            $liquidacion = Liquidacion::generar(
                proveedorId: $proveedorId,
                periodoInicio: $periodoInicio,
                periodoFin: $periodoFin,
                litrosTotales: $litrosTotales,
                precioLitro: $precioLitro,
                generadaEn: new DateTimeImmutable,
            );

            $guardada = $this->liquidaciones->guardar($liquidacion);
            LiquidacionEloquent::findOrFail($guardada->id)->update([
                'tarifas_aplicadas' => $tarifas, 'detalle_valorado' => $valorado['detalle'], 'monto_total' => $valorado['bruto'],
            ]);
            $sanciones = Sancion::where('proveedor_id', $proveedorId)
                ->where('estado', 'aprobada')
                ->whereNull('aplicada_liquidacion_id')
                ->lockForUpdate()
                ->get();
            $descuento = min((float) $sanciones->sum('descuento'), $valorado['bruto']);

            if ($descuento > 0 && $guardada->id !== null) {
                LiquidacionEloquent::whereKey($guardada->id)->update([
                    'descuento_sanciones' => $descuento,
                    'monto_total' => round($valorado['bruto'] - $descuento, 2),
                ]);
                Sancion::whereKey($sanciones->pluck('id')->all())->update(['aplicada_liquidacion_id' => $guardada->id]);
            }

            return $this->liquidaciones->buscarPorId($guardada->id) ?? $guardada;
        });
    }
}
