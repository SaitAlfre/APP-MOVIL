<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\Insumo as InsumoDominio;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Inventario\TipoMovimientoInsumo;
use App\Infrastructure\Persistence\Eloquent\Insumo as InsumoEloquent;
use App\Infrastructure\Persistence\Eloquent\MovimientoInsumo as MovimientoEloquent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentInsumoRepository implements InsumoRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator
    {
        return InsumoEloquent::query()
            ->orderBy('nombre')
            ->paginate($porPagina)
            ->through(fn (InsumoEloquent $i) => $this->aDominio($i));
    }

    public function todosActivos(): array
    {
        return InsumoEloquent::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (InsumoEloquent $i) => $this->aDominio($i))
            ->all();
    }

    public function conStockBajo(): array
    {
        return InsumoEloquent::query()
            ->where('activo', true)
            ->whereRaw('(existencia - reservado) < stock_minimo')
            ->orderBy('nombre')
            ->get()
            ->map(fn (InsumoEloquent $i) => $this->aDominio($i))
            ->all();
    }

    public function buscarPorId(int $id): ?InsumoDominio
    {
        $insumo = InsumoEloquent::query()->find($id);

        return $insumo !== null ? $this->aDominio($insumo) : null;
    }

    public function buscarPorNombre(string $nombre): ?InsumoDominio
    {
        $insumo = InsumoEloquent::query()->whereRaw('LOWER(nombre) = ?', [strtolower(trim($nombre))])->first();

        return $insumo !== null ? $this->aDominio($insumo) : null;
    }

    public function guardar(InsumoDominio $insumo): InsumoDominio
    {
        if ($insumo->id === null) {
            if ($this->buscarPorNombre($insumo->nombre) !== null) {
                throw InsumoInvalidoException::nombreDuplicado();
            }

            $registro = InsumoEloquent::query()->create([
                'nombre' => $insumo->nombre,
                'unidad' => $insumo->unidad,
                'stock_minimo' => $insumo->stockMinimo,
                'existencia' => $insumo->existencia,
                'reservado' => $insumo->reservado,
                'activo' => $insumo->activo,
            ]);

            return $this->aDominio($registro);
        }

        $registro = InsumoEloquent::query()->findOrFail($insumo->id);
        $registro->fill([
            'nombre' => $insumo->nombre,
            'unidad' => $insumo->unidad,
            'stock_minimo' => $insumo->stockMinimo,
            'activo' => $insumo->activo,
        ]);
        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    public function ajustar(int $insumoId, float $delta, string $motivo, int $usuarioId, ?string $observaciones): InsumoDominio
    {
        if (trim($motivo) === '') {
            throw InsumoInvalidoException::motivoAjusteObligatorio();
        }

        return DB::transaction(function () use ($insumoId, $delta, $motivo, $usuarioId, $observaciones) {
            $registro = InsumoEloquent::query()->whereKey($insumoId)->lockForUpdate()->firstOrFail();

            $nuevaExistencia = max(0.0, (float) $registro->existencia + $delta);

            $registro->update(['existencia' => $nuevaExistencia]);

            MovimientoEloquent::query()->create([
                'insumo_id' => $insumoId,
                'tipo' => TipoMovimientoInsumo::Ajuste,
                'cantidad' => $delta,
                'unidad' => $registro->unidad,
                'motivo' => trim($motivo),
                'usuario_id' => $usuarioId,
                'observaciones' => $observaciones,
                'fecha' => now(),
            ]);

            return $this->aDominio($registro->refresh());
        });
    }

    private function aDominio(InsumoEloquent $insumo): InsumoDominio
    {
        return InsumoDominio::reconstruir(
            id: $insumo->id,
            nombre: $insumo->nombre,
            unidad: $insumo->unidad,
            stockMinimo: (float) $insumo->stock_minimo,
            existencia: (float) $insumo->existencia,
            reservado: (float) $insumo->reservado,
            activo: $insumo->activo,
        );
    }
}
