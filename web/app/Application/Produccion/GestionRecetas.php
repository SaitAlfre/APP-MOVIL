<?php

namespace App\Application\Produccion;

use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Receta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GestionRecetas
{
    /** Factores para pasar el contenido de cada unidad del producto a la unidad en que rinde la receta. */
    private const CONVERSIONES = ['kg' => ['kg' => 1, 'g' => 0.001], 'L' => ['L' => 1, 'mL' => 0.001]];

    /**
     * Cuánto de la receta consume una unidad del producto. Si la receta rinde en kg o L y el producto
     * declara su peso o volumen (molde de 500 g), se usa ese contenido; si no, una unidad de receta.
     */
    public static function factorDeProducto(string $unidadReceta, ?float $contenido, ?string $unidadContenido): float
    {
        $conversion = self::CONVERSIONES[$unidadReceta][$unidadContenido ?? ''] ?? null;

        return $conversion !== null && $contenido !== null && $contenido > 0 ? $contenido * $conversion : 1.0;
    }

    /** Unidad en que se cuenta el producto: piezas si se deriva de su contenido; si no, la de la receta. */
    public static function unidadDeProducto(string $unidadReceta, ?float $contenido, ?string $unidadContenido): string
    {
        return isset(self::CONVERSIONES[$unidadReceta][$unidadContenido ?? '']) && $contenido !== null && $contenido > 0 ? 'unidad' : $unidadReceta;
    }

    /** Amarra el producto a una receta activa; una misma receta puede servir a varios productos. */
    public function vincular(int $productoId, int $recetaId): void
    {
        DB::transaction(function () use ($productoId, $recetaId): void {
            $receta = Receta::whereKey($recetaId)->lockForUpdate()->firstOrFail();
            $producto = Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();
            if (! $receta->activo && $producto->receta_id !== $recetaId) {
                throw ValidationException::withMessages(['receta_id' => 'Selecciona una receta activa.']);
            }
            $producto->receta_id = $recetaId;
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
            $datos = collect($datos)->except('ingredientes')->all();
            $litrosTanda = (float) ($datos['litros_tanda'] ?? $receta->litros_tanda);
            $rinde = (float) ($datos['rinde_unidades'] ?? $receta->rinde_unidades ?? 1);
            $receta->fill(['litros_por_unidad' => round($litrosTanda / $rinde, 6)] + $datos)->save();
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

    /**
     * Mantiene la proyección por unidad que consume Producción (leche e insumos de la tanda divididos
     * entre lo que rinde y multiplicados por el contenido del producto); el lote conserva su snapshot.
     */
    private function proyectar(Producto $producto, Receta $receta): void
    {
        $contenido = $producto->contenido_por_unidad !== null ? (float) $producto->contenido_por_unidad : null;
        $porUnidad = self::factorDeProducto($receta->unidad_base, $contenido, $producto->unidad_contenido) / (float) $receta->rinde_unidades;
        $producto->fill([
            'litros_por_unidad' => round((float) $receta->litros_tanda * $porUnidad, 6), 'otros_insumos' => $receta->otros_insumos, 'receta_activa' => $receta->activo,
            'unidad_produccion' => self::unidadDeProducto($receta->unidad_base, $contenido, $producto->unidad_contenido),
        ])->save();
        $ingredientes = DB::table('receta_materiales')->where('receta_id', $receta->id)->get()
            ->map(fn ($ingrediente): array => ['material_id' => $ingrediente->material_id, 'cantidad' => round((float) $ingrediente->cantidad * $porUnidad, 6)])->all();
        app(GestionIngredientes::class)->guardar($producto->id, $ingredientes);
    }

    private function snapshot(Receta $receta): array
    {
        return $receta->only(['nombre', 'unidad_base', 'litros_tanda', 'rinde_unidades', 'litros_por_unidad', 'otros_insumos', 'activo']) +
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
