<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Liquidaciones\EstadoLiquidacion;
use App\Domain\Liquidaciones\Liquidacion as LiquidacionDominio;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Liquidacion as LiquidacionEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentLiquidacionRepository implements LiquidacionRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator
    {
        return LiquidacionEloquent::query()
            ->orderByDesc('generada_en')
            ->paginate($porPagina)
            ->through(fn (LiquidacionEloquent $l) => $this->aDominio($l));
    }

    public function buscarPorId(int $id): ?LiquidacionDominio
    {
        $liquidacion = LiquidacionEloquent::query()->find($id);

        return $liquidacion !== null ? $this->aDominio($liquidacion) : null;
    }

    public function ultimasDelProveedor(int $proveedorId, int $limite = 10): array
    {
        return LiquidacionEloquent::query()
            ->where('proveedor_id', $proveedorId)
            ->orderByDesc('generada_en')
            ->limit($limite)
            ->get()
            ->map(fn (LiquidacionEloquent $l) => $this->aDominio($l))
            ->all();
    }

    public function contarPendientes(): int
    {
        return LiquidacionEloquent::query()->where('estado', EstadoLiquidacion::Pendiente->value)->count();
    }

    public function guardar(LiquidacionDominio $liquidacion): LiquidacionDominio
    {
        $registro = $liquidacion->id !== null
            ? LiquidacionEloquent::query()->findOrFail($liquidacion->id)
            : new LiquidacionEloquent;

        $registro->fill([
            'proveedor_id' => $liquidacion->proveedorId,
            'periodo_inicio' => $liquidacion->periodoInicio->format('Y-m-d'),
            'periodo_fin' => $liquidacion->periodoFin->format('Y-m-d'),
            'litros_totales' => $liquidacion->litrosTotales,
            'precio_litro' => $liquidacion->precioLitro,
            'monto_total' => $liquidacion->montoTotal,
            'estado' => $liquidacion->estado,
            'generada_en' => $liquidacion->generadaEn,
            'pagada_en' => $liquidacion->pagadaEn,
        ]);
        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    private function aDominio(LiquidacionEloquent $liquidacion): LiquidacionDominio
    {
        return LiquidacionDominio::reconstruir(
            id: $liquidacion->id,
            proveedorId: $liquidacion->proveedor_id,
            periodoInicio: DateTimeImmutable::createFromInterface($liquidacion->periodo_inicio),
            periodoFin: DateTimeImmutable::createFromInterface($liquidacion->periodo_fin),
            litrosTotales: (float) $liquidacion->litros_totales,
            precioLitro: (float) $liquidacion->precio_litro,
            montoTotal: (float) $liquidacion->monto_total,
            estado: $liquidacion->estado instanceof EstadoLiquidacion ? $liquidacion->estado : EstadoLiquidacion::from($liquidacion->estado),
            generadaEn: DateTimeImmutable::createFromInterface($liquidacion->generada_en),
            pagadaEn: $liquidacion->pagada_en !== null ? DateTimeImmutable::createFromInterface($liquidacion->pagada_en) : null,
        );
    }
}
