<?php

namespace App\Application\Produccion;

use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Receta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GestionRecetas
{
    public function vincular(int $productoId, ?int $recetaId, float $cantidad = 1): void
    {
        DB::transaction(function () use ($productoId, $recetaId, $cantidad): void {
            $receta = $recetaId !== null ? Receta::whereKey($recetaId)->lockForUpdate()->firstOrFail() : null;
            $producto = Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();
            if ($receta !== null && ! $receta->activo && $producto->receta_id !== $recetaId) {
                throw ValidationException::withMessages(['receta_id' => 'Selecciona una receta activa.']);
            }
            $producto->receta_id = $recetaId;
            $producto->cantidad_receta = $recetaId !== null ? $cantidad : 1;
            $this->proyectar($producto, $receta);
        });
    }

    public function actualizar(Receta $receta, array $datos, array $ingredientes): Receta
    {
        return DB::transaction(function () use ($receta, $datos, $ingredientes): Receta {
            if ($receta->exists) {
                $receta = Receta::whereKey($receta->id)->lockForUpdate()->firstOrFail();
            }
            $nueva = ! $receta->exists;
            $antes = $nueva ? null : $this->snapshot($receta);
            $receta->fill(collect($datos)->except('ingredientes')->all())->save();
            $receta->refresh();
            DB::table('receta_materiales')->where('receta_id', $receta->id)->delete();
            foreach ($ingredientes as $ingrediente) {
                DB::table('receta_materiales')->insert(['receta_id' => $receta->id, 'material_id' => $ingrediente['material_id'], 'cantidad' => $ingrediente['cantidad']]);
            }
            foreach (Producto::where('receta_id', $receta->id)->orderBy('id')->lockForUpdate()->get() as $producto) {
                $this->proyectar($producto, $receta);
            }

            $this->auditar($receta, $antes, $nueva ? 'crear' : 'actualizar');

            return $receta;
        });
    }

    public function cambiarEstado(Receta $receta, bool $activo): void
    {
        DB::transaction(function () use ($receta, $activo): void {
            $receta = Receta::whereKey($receta->id)->lockForUpdate()->firstOrFail();
            $antes = $this->snapshot($receta);
            $receta->update(['activo' => $activo]);
            foreach (Producto::where('receta_id', $receta->id)->orderBy('id')->lockForUpdate()->get() as $producto) {
                $this->proyectar($producto, $receta);
            }
            $this->auditar($receta, $antes, 'actualizar');
        });
    }

    /** Mantiene la proyección por producto que consume Producción; el lote conserva su propio snapshot. */
    private function proyectar(Producto $producto, ?Receta $receta): void
    {
        $factor = (float) $producto->cantidad_receta;
        $litros = $receta !== null ? round((float) $receta->litros_por_unidad * $factor, 3) : null;
        if ($receta !== null && ($litros < 0.001 || $litros > 999999999)) {
            throw ValidationException::withMessages(['cantidad_receta' => 'La leche por producto debe estar entre 0.001 y 999999999 litros.']);
        }
        $producto->fill(['litros_por_unidad' => $litros, 'otros_insumos' => $receta?->otros_insumos, 'receta_activa' => $receta?->activo ?? true])->save();
        $ingredientes = $receta !== null ? DB::table('receta_materiales')->where('receta_id', $receta->id)->get()->map(function ($i) use ($factor): array {
            $cantidad = round((float) $i->cantidad * $factor, 3);
            if ($cantidad < 0.001 || $cantidad > 999999999) {
                throw ValidationException::withMessages(['cantidad_receta' => 'La cantidad de cada ingrediente por producto debe estar entre 0.001 y 999999999.']);
            }

            return ['material_id' => $i->material_id, 'cantidad' => $cantidad];
        })->all() : [];
        app(GestionIngredientes::class)->guardar($producto->id, $ingredientes);
    }

    private function snapshot(Receta $receta): array
    {
        return $receta->only(['nombre', 'unidad_base', 'litros_por_unidad', 'otros_insumos', 'activo']) +
            ['ingredientes' => DB::table('receta_materiales')->where('receta_id', $receta->id)->orderBy('material_id')->get(['material_id', 'cantidad'])->toArray()];
    }

    private function auditar(Receta $receta, ?array $antes, string $accion): void
    {
        if (auth('operador')->id() === null) {
            return;
        }
        Auditoria::create(['entidad' => 'receta', 'entidad_id' => $receta->id, 'accion' => $accion,
            'valor_antes' => $antes !== null ? json_encode($antes) : null, 'valor_despues' => json_encode($this->snapshot($receta)),
            'motivo' => 'Gestión de receta compartida', 'usuario_id' => auth('operador')->id(), 'ocurrido_en' => now()]);
    }
}
