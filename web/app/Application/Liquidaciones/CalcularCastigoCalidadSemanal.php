<?php

namespace App\Application\Liquidaciones;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\EstadoLiquidacion;
use App\Infrastructure\Persistence\Eloquent\InfraccionCalidad;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Castigo por litro para la semana confirmada; dos infracciones nunca suman sus tarifas. */
final class CalcularCastigoCalidadSemanal
{
    public function __construct(private readonly EntregaRepositoryInterface $entregas) {}

    /**
     * @return array{descuento: float, regla: string, dias: list<array{fecha: string, litros: float, descuento_litro: float, descuento: float, infracciones: list<int>}>}
     */
    public function calcular(int $proveedorId, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $infracciones = InfraccionCalidad::query()->where('proveedor_id', $proveedorId)
            ->where('estado', 'confirmada')->whereNotNull('descuento_litro')
            ->whereDate('semana_inicio', '<=', $hasta->format('Y-m-d'))
            ->whereDate('semana_fin', '>=', $desde->format('Y-m-d'))->orderBy('id')->get();
        $dias = [];
        foreach ($this->entregas->litrosPorDiaDelProveedor($proveedorId, $desde, $hasta) as $dia) {
            $vigentes = $infracciones->filter(fn (InfraccionCalidad $infraccion) => $infraccion->semana_inicio->toDateString() <= $dia['fecha']
                && $infraccion->semana_fin->toDateString() >= $dia['fecha']);
            $tarifa = (float) ($vigentes->max('descuento_litro') ?? 0);
            if ($tarifa <= 0) {
                continue;
            }
            $dias[] = [
                'fecha' => $dia['fecha'], 'litros' => (float) $dia['litros'], 'descuento_litro' => $tarifa,
                'descuento' => round((float) $dia['litros'] * $tarifa, 2),
                'infracciones' => $vigentes->filter(fn (InfraccionCalidad $infraccion) => (float) $infraccion->descuento_litro === $tarifa)->pluck('id')->all(),
            ];
        }

        return ['descuento' => round(array_sum(array_column($dias, 'descuento')), 2), 'regla' => 'mayor_descuento_por_dia', 'dias' => $dias];
    }

    /** Conserva los descuentos históricos fijos y sustituye, sin acumular, el castigo semanal calculado. */
    public function recalcular(Liquidacion $liquidacion, float $bruto): void
    {
        if ($liquidacion->estado === EstadoLiquidacion::Pagada) {
            throw new RuntimeException('Una liquidación pagada no puede recalcular su castigo de calidad.');
        }
        $calculado = $this->calcular($liquidacion->proveedor_id,
            DateTimeImmutable::createFromInterface($liquidacion->periodo_inicio),
            DateTimeImmutable::createFromInterface($liquidacion->periodo_fin));
        $historicas = Sancion::query()->where('aplicada_liquidacion_id', $liquidacion->id)->get();
        $descuentoAnteriorCalidad = (float) ($liquidacion->detalle_castigo_calidad['descuento'] ?? 0);
        $fijo = $historicas->isNotEmpty() ? (float) $historicas->sum('descuento')
            : max(0, (float) $liquidacion->descuento_sanciones - $descuentoAnteriorCalidad);
        $fijo = min($fijo, $bruto);
        $calidad = min($calculado['descuento'], max(0, $bruto - $fijo));
        $liquidacion->forceFill([
            'detalle_castigo_calidad' => [...$calculado, 'descuento_calculado' => $calculado['descuento'], 'descuento' => $calidad],
            'descuento_sanciones' => round($fijo + $calidad, 2),
            'monto_total' => round(max(0, $bruto - $fijo - $calidad), 2),
        ])->save();
    }

    /** Revalora solo pagos pendientes, conservando los precios originales de cada fecha. */
    public function recalcularPendientes(int $proveedorId, string $desde, string $hasta): void
    {
        DB::transaction(function () use ($proveedorId, $desde, $hasta): void {
            $pendientes = Liquidacion::query()->where('proveedor_id', $proveedorId)->where('estado', EstadoLiquidacion::Pendiente)
                ->whereDate('periodo_inicio', '<=', $hasta)->whereDate('periodo_fin', '>=', $desde)
                ->orderBy('id')->lockForUpdate()->get();
            foreach ($pendientes as $liquidacion) {
                $inicio = DateTimeImmutable::createFromInterface($liquidacion->periodo_inicio);
                $fin = DateTimeImmutable::createFromInterface($liquidacion->periodo_fin);
                if ($liquidacion->tarifas_aplicadas !== null) {
                    $valorado = app(CalcularImporteTemporadas::class)->calcular($proveedorId, $inicio, $fin, $liquidacion->tarifas_aplicadas);
                    $bruto = $valorado['bruto'];
                    $liquidacion->forceFill(['litros_totales' => $valorado['litros'], 'detalle_valorado' => $valorado['detalle'], 'precio_litro' => $valorado['precio']]);
                } else {
                    $litros = $this->entregas->litrosPorProveedorEnRango($proveedorId, $inicio, $fin);
                    $bruto = round($litros * (float) $liquidacion->precio_litro, 2);
                    $liquidacion->litros_totales = $litros;
                }
                $this->recalcular($liquidacion, $bruto);
            }
        });
    }
}
