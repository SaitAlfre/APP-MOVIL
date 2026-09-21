<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $recetas = [
            ['nombre' => 'Queso Fresco 1kg', 'presentacion' => '1 kg', 'litros_por_unidad' => 8, 'otros_insumos' => 'Cuajo 2 mL, Sal 20 g'],
            ['nombre' => 'Queso Manchego', 'presentacion' => '900 g', 'litros_por_unidad' => 10, 'otros_insumos' => 'Cuajo 3 mL, Sal 25 g, Cultivo láctico 5 mL'],
            ['nombre' => 'Queso Mozzarella 500g', 'presentacion' => '500 g', 'litros_por_unidad' => 6, 'otros_insumos' => 'Cuajo 2 mL, Ácido cítrico 3 g'],
        ];

        foreach ($recetas as $receta) {
            Producto::query()->firstOrCreate(
                ['nombre' => $receta['nombre']],
                [
                    'presentacion' => $receta['presentacion'],
                    'unidad_produccion' => 'unidad',
                    'contenido_por_unidad' => null,
                    'unidad_contenido' => null,
                    'litros_por_unidad' => $receta['litros_por_unidad'],
                    'otros_insumos' => $receta['otros_insumos'],
                    'existencia' => 0,
                    'activo' => true,
                ],
            );
        }
    }
}
