<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\TipoMovimientoInsumo;
use App\Domain\Produccion\EstadoLoteProduccion;
use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteInsumo as LoteInsumoDominio;
use App\Domain\Produccion\LoteProduccion as LoteDominio;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Insumo as InsumoEloquent;
use App\Infrastructure\Persistence\Eloquent\LoteInsumo as LoteInsumoEloquent;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion as LoteEloquent;
use App\Infrastructure\Persistence\Eloquent\MovimientoInsumo as MovimientoInsumoEloquent;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto as MovimientoProductoEloquent;
use App\Infrastructure\Persistence\Eloquent\Producto as ProductoEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentLoteProduccionRepository implements LoteProduccionRepositoryInterface
{
    public function paginar(int $porPagina = 20, ?EstadoLoteProduccion $estado = null, ?int $productoId = null): LengthAwarePaginator
    {
        return LoteEloquent::query()
            ->when($estado !== null, fn ($query) => $query->where('estado', $estado->value))
            ->when($productoId !== null, fn ($query) => $query->where('producto_id', $productoId))
            ->orderByDesc('abierto_en')
            ->paginate($porPagina)
            ->through(fn (LoteEloquent $l) => $this->aDominio($l));
    }

    public function buscarPorId(int $id): ?LoteDominio
    {
        $lote = LoteEloquent::query()->find($id);

        return $lote !== null ? $this->aDominio($lote) : null;
    }

    public function buscarPorCodigo(string $codigo): ?LoteDominio
    {
        $lote = LoteEloquent::query()->where('codigo', $codigo)->first();

        return $lote !== null ? $this->aDominio($lote) : null;
    }

    public function insumosDelLote(int $loteId): array
    {
        return LoteInsumoEloquent::query()
            ->where('lote_produccion_id', $loteId)
            ->orderBy('insumo_id')
            ->get()
            ->map(fn (LoteInsumoEloquent $li) => $this->insumoADominio($li))
            ->all();
    }

    public function crear(LoteDominio $lote, array $necesidades): LoteDominio
    {
        return DB::transaction(function () use ($lote, $necesidades) {
            $registro = LoteEloquent::query()->create([
                'codigo' => $lote->codigo,
                'producto_id' => $lote->productoId,
                'receta_id' => $lote->recetaId,
                'cantidad_planificada' => $lote->cantidadPlanificada,
                'unidad' => $lote->unidad,
                'estado' => EstadoLoteProduccion::Borrador->value,
                'responsable_usuario_id' => $lote->responsableUsuarioId,
                'fecha_planificada' => $lote->fechaPlanificada,
                'observaciones' => $lote->observaciones,
                'abierto_en' => $lote->creadoEn,
            ]);

            foreach ($necesidades as $necesidad) {
                LoteInsumoEloquent::query()->create([
                    'lote_produccion_id' => $registro->id,
                    'insumo_id' => $necesidad->insumoId,
                    'unidad' => $necesidad->unidad,
                    'cantidad_necesaria' => $necesidad->cantidad,
                    'cantidad_reservada' => 0,
                    'cantidad_consumida' => null,
                ]);
            }

            return $this->aDominio($registro);
        });
    }

    public function iniciar(int $loteId): LoteDominio
    {
        return DB::transaction(function () use ($loteId) {
            $lote = LoteEloquent::query()->whereKey($loteId)->lockForUpdate()->firstOrFail();

            if ($lote->estado !== EstadoLoteProduccion::Borrador) {
                throw LoteProduccionInvalidoException::noEstaEnBorrador();
            }

            $filas = LoteInsumoEloquent::query()
                ->where('lote_produccion_id', $loteId)
                ->orderBy('insumo_id')
                ->get();

            $insumos = InsumoEloquent::query()
                ->whereIn('id', $filas->pluck('insumo_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($filas as $fila) {
                $insumo = $insumos[$fila->insumo_id];
                $disponible = round((float) $insumo->existencia - (float) $insumo->reservado, 3);

                if ($disponible < (float) $fila->cantidad_necesaria) {
                    throw InsumoInvalidoException::disponibilidadInsuficiente(
                        $insumo->nombre,
                        (float) $fila->cantidad_necesaria,
                        $disponible,
                        $fila->unidad,
                    );
                }
            }

            foreach ($filas as $fila) {
                $insumo = $insumos[$fila->insumo_id];
                $insumo->update(['reservado' => (float) $insumo->reservado + (float) $fila->cantidad_necesaria]);
                $fila->update(['cantidad_reservada' => $fila->cantidad_necesaria]);

                MovimientoInsumoEloquent::query()->create([
                    'insumo_id' => $fila->insumo_id,
                    'tipo' => TipoMovimientoInsumo::Reserva,
                    'cantidad' => $fila->cantidad_necesaria,
                    'unidad' => $fila->unidad,
                    'lote_produccion_id' => $loteId,
                    'usuario_id' => $lote->responsable_usuario_id,
                    'fecha' => now(),
                ]);
            }

            $lote->update(['estado' => EstadoLoteProduccion::EnProceso->value, 'iniciado_en' => now()]);

            return $this->aDominio($lote->refresh());
        });
    }

    public function registrarConsumo(int $loteId, array $consumos, int $usuarioId): LoteDominio
    {
        return DB::transaction(function () use ($loteId, $consumos, $usuarioId) {
            $lote = LoteEloquent::query()->whereKey($loteId)->lockForUpdate()->firstOrFail();

            if ($lote->estado !== EstadoLoteProduccion::EnProceso) {
                throw LoteProduccionInvalidoException::noEstaEnProceso();
            }

            $this->aplicarConsumos($loteId, $consumos, $usuarioId);

            return $this->aDominio($lote->refresh());
        });
    }

    public function finalizar(int $loteId, float $cantidadObtenida, ?array $consumosFinales, int $usuarioId): LoteDominio
    {
        if ($cantidadObtenida < 0.0) {
            throw LoteProduccionInvalidoException::cantidadObtenidaInvalida();
        }

        return DB::transaction(function () use ($loteId, $cantidadObtenida, $consumosFinales, $usuarioId) {
            $lote = LoteEloquent::query()->whereKey($loteId)->lockForUpdate()->firstOrFail();

            if ($lote->estado !== EstadoLoteProduccion::EnProceso) {
                throw LoteProduccionInvalidoException::noEstaEnProceso();
            }

            if ($consumosFinales !== null && $consumosFinales !== []) {
                $this->aplicarConsumos($loteId, $consumosFinales, $usuarioId);
            }

            $filas = LoteInsumoEloquent::query()
                ->where('lote_produccion_id', $loteId)
                ->with('insumo')
                ->orderBy('insumo_id')
                ->get();

            foreach ($filas as $fila) {
                if ($fila->cantidad_consumida === null) {
                    throw LoteProduccionInvalidoException::consumoNoInformado($fila->insumo->nombre);
                }
            }

            foreach ($filas as $fila) {
                $sobrante = round((float) $fila->cantidad_reservada - (float) $fila->cantidad_consumida, 3);

                if ($sobrante > 0) {
                    $insumo = InsumoEloquent::query()->whereKey($fila->insumo_id)->lockForUpdate()->firstOrFail();
                    $insumo->update(['reservado' => max(0.0, (float) $insumo->reservado - $sobrante)]);

                    MovimientoInsumoEloquent::query()->create([
                        'insumo_id' => $fila->insumo_id,
                        'tipo' => TipoMovimientoInsumo::Liberacion,
                        'cantidad' => $sobrante,
                        'unidad' => $fila->unidad,
                        'lote_produccion_id' => $loteId,
                        'usuario_id' => $usuarioId,
                        'fecha' => now(),
                    ]);
                }
            }

            $producto = ProductoEloquent::query()->whereKey($lote->producto_id)->lockForUpdate()->firstOrFail();
            $producto->update(['existencia' => (float) $producto->existencia + $cantidadObtenida]);

            MovimientoProductoEloquent::query()->create([
                'producto_id' => $lote->producto_id,
                'tipo' => 'produccion',
                'cantidad' => $cantidadObtenida,
                'unidad' => $lote->unidad,
                'lote_produccion_id' => $loteId,
                'usuario_id' => $usuarioId,
                'fecha' => now(),
            ]);

            $lote->update([
                'estado' => EstadoLoteProduccion::Finalizado->value,
                'cantidad_obtenida' => $cantidadObtenida,
                'finalizado_en' => now(),
            ]);

            return $this->aDominio($lote->refresh());
        });
    }

    public function cancelar(int $loteId, string $motivo, int $usuarioId): LoteDominio
    {
        if (trim($motivo) === '') {
            throw LoteProduccionInvalidoException::motivoCancelacionObligatorio();
        }

        return DB::transaction(function () use ($loteId, $motivo, $usuarioId) {
            $lote = LoteEloquent::query()->whereKey($loteId)->lockForUpdate()->firstOrFail();
            $estadoActual = $lote->estado;

            if (! in_array($estadoActual, [EstadoLoteProduccion::Borrador, EstadoLoteProduccion::EnProceso], true)) {
                throw LoteProduccionInvalidoException::noSePuedeCancelar();
            }

            if ($estadoActual === EstadoLoteProduccion::EnProceso) {
                $filas = LoteInsumoEloquent::query()->where('lote_produccion_id', $loteId)->orderBy('insumo_id')->get();

                foreach ($filas as $fila) {
                    $sobrante = round((float) $fila->cantidad_reservada - (float) ($fila->cantidad_consumida ?? 0.0), 3);

                    if ($sobrante > 0) {
                        $insumo = InsumoEloquent::query()->whereKey($fila->insumo_id)->lockForUpdate()->firstOrFail();
                        $insumo->update(['reservado' => max(0.0, (float) $insumo->reservado - $sobrante)]);

                        MovimientoInsumoEloquent::query()->create([
                            'insumo_id' => $fila->insumo_id,
                            'tipo' => TipoMovimientoInsumo::Liberacion,
                            'cantidad' => $sobrante,
                            'unidad' => $fila->unidad,
                            'lote_produccion_id' => $loteId,
                            'motivo' => 'Cancelación de lote: '.trim($motivo),
                            'usuario_id' => $usuarioId,
                            'fecha' => now(),
                        ]);
                    }
                }
            }

            $lote->update([
                'estado' => EstadoLoteProduccion::Cancelado->value,
                'motivo_cancelacion' => trim($motivo),
                'cancelado_en' => now(),
            ]);

            return $this->aDominio($lote->refresh());
        });
    }

    public function resumen(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        return [
            'borradores' => LoteEloquent::query()->where('estado', EstadoLoteProduccion::Borrador->value)->count(),
            'en_proceso' => LoteEloquent::query()->where('estado', EstadoLoteProduccion::EnProceso->value)->count(),
            'finalizados_periodo' => LoteEloquent::query()
                ->where('estado', EstadoLoteProduccion::Finalizado->value)
                ->whereBetween('finalizado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
                ->count(),
            'cantidad_producida_periodo' => (float) LoteEloquent::query()
                ->where('estado', EstadoLoteProduccion::Finalizado->value)
                ->whereBetween('finalizado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
                ->sum('cantidad_obtenida'),
        ];
    }

    /** @param array<int, float> $consumos */
    private function aplicarConsumos(int $loteId, array $consumos, int $usuarioId): void
    {
        foreach ($consumos as $insumoId => $cantidadTotal) {
            $fila = LoteInsumoEloquent::query()
                ->where('lote_produccion_id', $loteId)
                ->where('insumo_id', $insumoId)
                ->lockForUpdate()
                ->first();

            if ($fila === null) {
                continue;
            }

            $previo = $fila->cantidad_consumida !== null ? (float) $fila->cantidad_consumida : 0.0;
            $nuevo = (float) $cantidadTotal;

            if ($nuevo < 0.0 || $nuevo > (float) $fila->cantidad_reservada + 0.0005) {
                throw LoteProduccionInvalidoException::consumoInvalido();
            }

            $delta = round($nuevo - $previo, 3);

            if (abs($delta) > 0.0) {
                $insumo = InsumoEloquent::query()->whereKey($insumoId)->lockForUpdate()->firstOrFail();
                $insumo->update([
                    'existencia' => max(0.0, (float) $insumo->existencia - $delta),
                    'reservado' => max(0.0, (float) $insumo->reservado - $delta),
                ]);

                MovimientoInsumoEloquent::query()->create([
                    'insumo_id' => $insumoId,
                    'tipo' => TipoMovimientoInsumo::Consumo,
                    'cantidad' => $delta,
                    'unidad' => $fila->unidad,
                    'lote_produccion_id' => $loteId,
                    'usuario_id' => $usuarioId,
                    'fecha' => now(),
                ]);
            }

            $fila->update(['cantidad_consumida' => $nuevo]);
        }
    }

    private function insumoADominio(LoteInsumoEloquent $fila): LoteInsumoDominio
    {
        return new LoteInsumoDominio(
            id: $fila->id,
            loteProduccionId: $fila->lote_produccion_id,
            insumoId: $fila->insumo_id,
            unidad: $fila->unidad,
            cantidadNecesaria: (float) $fila->cantidad_necesaria,
            cantidadReservada: (float) $fila->cantidad_reservada,
            cantidadConsumida: $fila->cantidad_consumida !== null ? (float) $fila->cantidad_consumida : null,
        );
    }

    private function aDominio(LoteEloquent $lote): LoteDominio
    {
        return LoteDominio::reconstruir(
            id: $lote->id,
            codigo: $lote->codigo,
            productoId: $lote->producto_id,
            recetaId: $lote->receta_id,
            cantidadPlanificada: (float) $lote->cantidad_planificada,
            cantidadObtenida: $lote->cantidad_obtenida !== null ? (float) $lote->cantidad_obtenida : null,
            unidad: $lote->unidad,
            estado: $lote->estado instanceof EstadoLoteProduccion ? $lote->estado : EstadoLoteProduccion::from($lote->estado),
            responsableUsuarioId: $lote->responsable_usuario_id,
            fechaPlanificada: DateTimeImmutable::createFromInterface($lote->fecha_planificada),
            observaciones: $lote->observaciones,
            creadoEn: DateTimeImmutable::createFromInterface($lote->abierto_en),
            iniciadoEn: $lote->iniciado_en !== null ? DateTimeImmutable::createFromInterface($lote->iniciado_en) : null,
            finalizadoEn: $lote->finalizado_en !== null ? DateTimeImmutable::createFromInterface($lote->finalizado_en) : null,
            canceladoEn: $lote->cancelado_en !== null ? DateTimeImmutable::createFromInterface($lote->cancelado_en) : null,
            motivoCancelacion: $lote->motivo_cancelacion,
        );
    }
}
