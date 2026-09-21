<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\Producto as ProductoDominio;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Producto as ProductoEloquent;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentProductoRepository implements ProductoRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator
    {
        return ProductoEloquent::query()
            ->orderBy('nombre')
            ->paginate($porPagina)
            ->through(fn (ProductoEloquent $p) => $this->aDominio($p));
    }

    public function todosActivos(): array
    {
        return ProductoEloquent::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (ProductoEloquent $p) => $this->aDominio($p))
            ->all();
    }

    public function buscarPorId(int $id): ?ProductoDominio
    {
        $producto = ProductoEloquent::query()->find($id);

        return $producto !== null ? $this->aDominio($producto) : null;
    }

    public function buscarPorNombre(string $nombre): ?ProductoDominio
    {
        $producto = ProductoEloquent::query()->whereRaw('LOWER(nombre) = ?', [strtolower(trim($nombre))])->first();

        return $producto !== null ? $this->aDominio($producto) : null;
    }

    public function guardar(ProductoDominio $producto): ProductoDominio
    {
        if ($producto->id === null) {
            if ($this->buscarPorNombre($producto->nombre) !== null) {
                throw ProductoInvalidoException::nombreDuplicado();
            }

            $registro = ProductoEloquent::query()->create([
                'nombre' => $producto->nombre,
                'presentacion' => $producto->presentacion,
                'unidad_produccion' => $producto->unidadProduccion,
                'contenido_por_unidad' => $producto->contenidoPorUnidad,
                'unidad_contenido' => $producto->unidadContenido,
                'litros_por_unidad' => $producto->litrosPorUnidad,
                'otros_insumos' => $producto->otrosInsumos,
                'existencia' => $producto->existencia,
                'activo' => $producto->activo,
            ]);

            return $this->aDominio($registro);
        }

        $registro = ProductoEloquent::query()->findOrFail($producto->id);
        $registro->fill([
            'nombre' => $producto->nombre,
            'presentacion' => $producto->presentacion,
            'unidad_produccion' => $producto->unidadProduccion,
            'contenido_por_unidad' => $producto->contenidoPorUnidad,
            'unidad_contenido' => $producto->unidadContenido,
            'litros_por_unidad' => $producto->litrosPorUnidad,
            'otros_insumos' => $producto->otrosInsumos,
        ]);
        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    public function cambiarEstado(int $id, bool $activo): ProductoDominio
    {
        $registro = ProductoEloquent::query()->findOrFail($id);
        $registro->update(['activo' => $activo]);

        return $this->aDominio($registro->refresh());
    }

    private function aDominio(ProductoEloquent $producto): ProductoDominio
    {
        return ProductoDominio::reconstruir(
            id: $producto->id,
            nombre: $producto->nombre,
            presentacion: $producto->presentacion,
            unidadProduccion: $producto->unidad_produccion,
            contenidoPorUnidad: $producto->contenido_por_unidad !== null ? (float) $producto->contenido_por_unidad : null,
            unidadContenido: $producto->unidad_contenido,
            litrosPorUnidad: (float) $producto->litros_por_unidad,
            otrosInsumos: $producto->otros_insumos,
            existencia: (float) $producto->existencia,
            activo: $producto->activo,
        );
    }
}
