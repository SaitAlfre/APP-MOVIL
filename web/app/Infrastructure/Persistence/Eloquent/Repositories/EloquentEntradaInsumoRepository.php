<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Inventario\EntradaInsumo as EntradaDominio;
use App\Domain\Inventario\EntradaInsumoRepositoryInterface;
use App\Domain\Inventario\MovimientoInsumo as MovimientoDominio;
use App\Domain\Inventario\TipoMovimientoInsumo;
use App\Infrastructure\Persistence\Eloquent\EntradaInsumo as EntradaEloquent;
use App\Infrastructure\Persistence\Eloquent\Insumo as InsumoEloquent;
use App\Infrastructure\Persistence\Eloquent\MovimientoInsumo as MovimientoEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentEntradaInsumoRepository implements EntradaInsumoRepositoryInterface
{
    public function paginarPorInsumo(int $insumoId, int $porPagina = 20): LengthAwarePaginator
    {
        return EntradaEloquent::query()
            ->where('insumo_id', $insumoId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->through(fn (EntradaEloquent $e) => $this->aDominio($e));
    }

    public function buscarPorEntregaId(int $entregaId): ?EntradaDominio
    {
        $entrada = EntradaEloquent::query()->where('entrega_id', $entregaId)->first();

        return $entrada !== null ? $this->aDominio($entrada) : null;
    }

    public function registrar(EntradaDominio $entrada): EntradaDominio
    {
        return DB::transaction(function () use ($entrada) {
            if ($entrada->entregaId !== null && $this->buscarPorEntregaId($entrada->entregaId) !== null) {
                // Idempotencia: esta entrega ya generó su entrada de inventario, no se duplica.
                return $this->buscarPorEntregaId($entrada->entregaId);
            }

            $registro = EntradaEloquent::query()->create([
                'insumo_id' => $entrada->insumoId,
                'proveedor_id' => $entrada->proveedorId,
                'entrega_id' => $entrada->entregaId,
                'cantidad' => $entrada->cantidad,
                'unidad' => $entrada->unidad,
                'fecha' => $entrada->fecha,
                'costo_unitario' => $entrada->costoUnitario,
                'costo_total' => $entrada->costoTotal,
                'documento_referencia' => $entrada->documentoReferencia,
                'lote_origen' => $entrada->loteOrigen,
                'vencimiento' => $entrada->vencimiento,
                'observaciones' => $entrada->observaciones,
                'usuario_id' => $entrada->usuarioId,
            ]);

            $insumo = InsumoEloquent::query()->whereKey($entrada->insumoId)->lockForUpdate()->firstOrFail();
            $insumo->update(['existencia' => (float) $insumo->existencia + $entrada->cantidad]);

            MovimientoEloquent::query()->create([
                'insumo_id' => $entrada->insumoId,
                'tipo' => TipoMovimientoInsumo::Entrada,
                'cantidad' => $entrada->cantidad,
                'unidad' => $entrada->unidad,
                'entrada_insumo_id' => $registro->id,
                'usuario_id' => $entrada->usuarioId,
                'observaciones' => $entrada->observaciones,
                'fecha' => $entrada->fecha,
            ]);

            return $this->aDominio($registro->refresh());
        });
    }

    public function historialPorInsumo(int $insumoId, int $porPagina = 20): LengthAwarePaginator
    {
        return MovimientoEloquent::query()
            ->where('insumo_id', $insumoId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->through(fn (MovimientoEloquent $m) => new MovimientoDominio(
                id: $m->id,
                insumoId: $m->insumo_id,
                tipo: $m->tipo instanceof TipoMovimientoInsumo ? $m->tipo : TipoMovimientoInsumo::from($m->tipo),
                cantidad: (float) $m->cantidad,
                unidad: $m->unidad,
                loteProduccionId: $m->lote_produccion_id,
                entradaInsumoId: $m->entrada_insumo_id,
                motivo: $m->motivo,
                usuarioId: $m->usuario_id,
                observaciones: $m->observaciones,
                fecha: DateTimeImmutable::createFromInterface($m->fecha),
            ));
    }

    private function aDominio(EntradaEloquent $entrada): EntradaDominio
    {
        return EntradaDominio::reconstruir(
            id: $entrada->id,
            insumoId: $entrada->insumo_id,
            proveedorId: $entrada->proveedor_id,
            entregaId: $entrada->entrega_id,
            cantidad: (float) $entrada->cantidad,
            unidad: $entrada->unidad,
            fecha: DateTimeImmutable::createFromInterface($entrada->fecha),
            costoUnitario: $entrada->costo_unitario !== null ? (float) $entrada->costo_unitario : null,
            costoTotal: $entrada->costo_total !== null ? (float) $entrada->costo_total : null,
            documentoReferencia: $entrada->documento_referencia,
            loteOrigen: $entrada->lote_origen,
            vencimiento: $entrada->vencimiento !== null ? DateTimeImmutable::createFromInterface($entrada->vencimiento) : null,
            observaciones: $entrada->observaciones,
            usuarioId: $entrada->usuario_id,
        );
    }
}
