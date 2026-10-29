<?php

namespace Database\Seeders;

use App\Application\Produccion\GestionRecetas;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Receta;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /** Cada producto sale de su propia receta, descrita como una tanda de 100 L. */
    public function run(): void
    {
        $recetas = [
            ['nombre' => 'Queso Fresco 1kg', 'presentacion' => '1 kg', 'rinde' => 12.5, 'otros_insumos' => 'Cuajo 2 mL, Sal 20 g por unidad'],
            ['nombre' => 'Queso Manchego', 'presentacion' => '900 g', 'rinde' => 10, 'otros_insumos' => 'Cuajo 3 mL, Sal 25 g, Cultivo láctico 5 mL por unidad'],
            ['nombre' => 'Queso Mozzarella 500g', 'presentacion' => '500 g', 'rinde' => 100 / 6, 'otros_insumos' => 'Cuajo 2 mL, Ácido cítrico 3 g por unidad'],
        ];
        $gestion = app(GestionRecetas::class);

        foreach ($recetas as $datos) {
            $producto = Producto::query()->firstOrCreate(
                ['nombre' => $datos['nombre']],
                ['presentacion' => $datos['presentacion'], 'unidad_produccion' => 'unidad', 'existencia' => 0, 'activo' => true],
            );
            if ($producto->receta_id !== null) {
                continue;
            }
            $receta = Receta::query()->where('nombre', $datos['nombre'])->first()
                ?? $gestion->actualizar(new Receta, ['nombre' => $datos['nombre'], 'unidad_base' => 'unidad', 'litros_tanda' => 100,
                    'rinde_unidades' => round($datos['rinde'], 3), 'otros_insumos' => $datos['otros_insumos']], []);
            $gestion->vincular($producto->id, $receta->id);
        }
    }
}
