<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Insumo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insumo>
 */
class InsumoFactory extends Factory
{
    protected $model = Insumo::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'unidad' => fake()->randomElement(['kg', 'g', 'L', 'ml', 'unidad']),
            'stock_minimo' => 0,
            'existencia' => 0,
            'reservado' => 0,
            'activo' => true,
        ];
    }
}
