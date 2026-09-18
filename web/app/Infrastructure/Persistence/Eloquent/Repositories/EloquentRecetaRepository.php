<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Recetas\EstadoReceta;
use App\Domain\Recetas\Exceptions\RecetaInvalidaException;
use App\Domain\Recetas\Receta as RecetaDominio;
use App\Domain\Recetas\RecetaIngrediente as RecetaIngredienteDominio;
use App\Domain\Recetas\RecetaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion as LoteEloquent;
use App\Infrastructure\Persistence\Eloquent\Producto as ProductoEloquent;
use App\Infrastructure\Persistence\Eloquent\Receta as RecetaEloquent;
use App\Infrastructure\Persistence\Eloquent\RecetaIngrediente as IngredienteEloquent;
use Illuminate\Support\Facades\DB;

final class EloquentRecetaRepository implements RecetaRepositoryInterface
{
    public function listarPorProducto(int $productoId): array
    {
        return RecetaEloquent::query()
            ->where('producto_id', $productoId)
            ->with('ingredientes')
            ->orderByDesc('version')
            ->get()
            ->map(fn (RecetaEloquent $r) => $this->aDominio($r))
            ->all();
    }

    public function buscarPorId(int $id): ?RecetaDominio
    {
        $receta = RecetaEloquent::query()->with('ingredientes')->find($id);

        return $receta !== null ? $this->aDominio($receta) : null;
    }

    public function buscarActivaPorProducto(int $productoId): ?RecetaDominio
    {
        $receta = RecetaEloquent::query()
            ->where('producto_id', $productoId)
            ->where('estado', EstadoReceta::Activa->value)
            ->with('ingredientes')
            ->first();

        return $receta !== null ? $this->aDominio($receta) : null;
    }

    public function ultimaVersion(int $productoId): int
    {
        return (int) (RecetaEloquent::query()->where('producto_id', $productoId)->max('version') ?? 0);
    }

    public function guardar(RecetaDominio $receta): RecetaDominio
    {
        return DB::transaction(function () use ($receta) {
            $registro = RecetaEloquent::query()->create([
                'producto_id' => $receta->productoId,
                'nombre' => $receta->nombre,
                'version' => $receta->version,
                'rendimiento_base' => $receta->rendimientoBase,
                'rendimiento_unidad' => $receta->rendimientoUnidad,
                'observaciones' => $receta->observaciones,
                'estado' => $receta->estado->value,
                'creado_por_usuario_id' => $receta->creadoPorUsuarioId,
            ]);

            foreach ($receta->ingredientes as $ingrediente) {
                IngredienteEloquent::query()->create([
                    'receta_id' => $registro->id,
                    'insumo_id' => $ingrediente->insumoId,
                    'cantidad' => $ingrediente->cantidad,
                    'unidad' => $ingrediente->unidad,
                ]);
            }

            return $this->aDominio($registro->load('ingredientes'));
        });
    }

    public function actualizar(RecetaDominio $receta): RecetaDominio
    {
        return DB::transaction(function () use ($receta) {
            $registro = RecetaEloquent::query()->findOrFail($receta->id);
            $registro->fill([
                'nombre' => $receta->nombre,
                'rendimiento_base' => $receta->rendimientoBase,
                'rendimiento_unidad' => $receta->rendimientoUnidad,
                'observaciones' => $receta->observaciones,
            ]);
            $registro->save();

            $registro->ingredientes()->delete();

            foreach ($receta->ingredientes as $ingrediente) {
                IngredienteEloquent::query()->create([
                    'receta_id' => $registro->id,
                    'insumo_id' => $ingrediente->insumoId,
                    'cantidad' => $ingrediente->cantidad,
                    'unidad' => $ingrediente->unidad,
                ]);
            }

            return $this->aDominio($registro->refresh()->load('ingredientes'));
        });
    }

    public function activar(int $recetaId): RecetaDominio
    {
        return DB::transaction(function () use ($recetaId) {
            $registro = RecetaEloquent::query()->whereKey($recetaId)->lockForUpdate()->firstOrFail();

            if ($registro->estado === EstadoReceta::Archivada || $registro->estado->value === EstadoReceta::Archivada->value) {
                throw RecetaInvalidaException::noPuedeActivarseArchivada();
            }

            RecetaEloquent::query()
                ->where('producto_id', $registro->producto_id)
                ->where('estado', EstadoReceta::Activa->value)
                ->where('id', '!=', $recetaId)
                ->update(['estado' => EstadoReceta::Archivada->value]);

            $registro->update(['estado' => EstadoReceta::Activa->value]);

            ProductoEloquent::query()->whereKey($registro->producto_id)->update(['receta_activa_id' => $recetaId]);

            return $this->aDominio($registro->refresh()->load('ingredientes'));
        });
    }

    public function fueUtilizadaEnLotes(int $recetaId): bool
    {
        return LoteEloquent::query()->where('receta_id', $recetaId)->exists();
    }

    private function aDominio(RecetaEloquent $receta): RecetaDominio
    {
        $ingredientes = $receta->ingredientes
            ->map(fn (IngredienteEloquent $i) => RecetaIngredienteDominio::crear($i->insumo_id, (float) $i->cantidad, $i->unidad))
            ->all();

        return RecetaDominio::reconstruir(
            id: $receta->id,
            productoId: $receta->producto_id,
            nombre: $receta->nombre,
            version: $receta->version,
            rendimientoBase: (float) $receta->rendimiento_base,
            rendimientoUnidad: $receta->rendimiento_unidad,
            observaciones: $receta->observaciones,
            estado: $receta->estado instanceof EstadoReceta ? $receta->estado : EstadoReceta::from($receta->estado),
            creadoPorUsuarioId: $receta->creado_por_usuario_id,
            ingredientes: $ingredientes,
        );
    }
}
