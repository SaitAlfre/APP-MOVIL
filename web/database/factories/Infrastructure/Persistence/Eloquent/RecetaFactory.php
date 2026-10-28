<?php

namespace Database\Factories\Infrastructure\Persistence\Eloquent;

use App\Infrastructure\Persistence\Eloquent\Receta;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Receta> */
class RecetaFactory extends Factory
{
    protected $model = Receta::class;

    public function definition(): array
    {
        return ['nombre' => fake()->unique()->words(3, true), 'unidad_base' => 'unidad', 'litros_por_unidad' => 10, 'activo' => true];
    }
}
