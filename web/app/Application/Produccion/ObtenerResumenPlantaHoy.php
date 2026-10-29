<?php

namespace App\Application\Produccion;

use App\Infrastructure\Persistence\Eloquent\Material;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Lo que pasa hoy en planta: leche del día, lotes del día e insumos por reponer. Alimenta los paneles laterales. */
final class ObtenerResumenPlantaHoy
{
    public function __construct(
        private readonly ObtenerSaldoProduccionUseCase $saldo,
    ) {}

    /**
     * @return array{
     *     leche: array{total: float, asignada: float, disponible: float},
     *     lotes: Collection<int, object>,
     *     insumosBajos: Collection<int, Material>
     * }
     */
    public function ejecutar(): array
    {
        $hoy = new DateTimeImmutable('today');
        $saldo = $this->saldo->ejecutar($hoy);

        return [
            'leche' => [
                'total' => $saldo->acopio->litrosTotal(),
                'asignada' => $saldo->litrosAsignados,
                'disponible' => $saldo->litrosDisponibles(),
            ],
            'lotes' => DB::table('lotes_produccion as lote')->join('productos as producto', 'producto.id', '=', 'lote.producto_id')
                ->whereDate('lote.fecha', $hoy->format('Y-m-d'))->orderBy('lote.id')
                ->get(['lote.id', 'lote.codigo', 'lote.estado', 'lote.litros_asignados', 'lote.unidades_estimadas', 'lote.unidades_producidas', 'lote.created_at', 'lote.iniciado_en', 'lote.finalizado_en', 'producto.nombre as producto']),
            'insumosBajos' => Material::query()->where(fn ($q) => $q->where('existencia', '<=', 0)
                ->orWhere(fn ($q) => $q->whereNotNull('stock_minimo')->whereColumn('existencia', '<=', 'stock_minimo')))
                ->orderBy('existencia')->limit(6)->get(),
        ];
    }
}
