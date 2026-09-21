<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'presentacion' => '1 kg',
            'unidad_produccion' => 'unidad',
            'contenido_por_unidad' => 1,
            'unidad_contenido' => 'kg',
            'litros_por_unidad' => 10,
            'otros_insumos' => null,
            'existencia' => 0,
            'activo' => true,
        ];
    }
}
